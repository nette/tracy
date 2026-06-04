# Debugger bootstrap & error handling

`Debugger::enable()` wires PHP's error machinery; the ordering is the non-local
knowledge.

## `enable()` order

1. **Mode gate** — sets `$productionMode` (an explicit bool is used directly, else
   `!detectDebugMode($mode)`), but **only** when a `$mode` argument is passed or the
   mode is still `Detect` — a repeated `enable()` without `$mode` does *not*
   re-evaluate an already-resolved mode.
2. **Reserve memory / record `$time` / record `$obLevel`** — all `??=`, so **first
   call only**; a later `enable()` after more `ob_start()`s does not move `$obLevel`.
   Note **`ob_start()` is never called** — Tracy does *not* run its own output buffer;
   it only remembers the buffer level and later strips buffers *above* it
   (`removeOutputBuffers`).
3. Logging config (only overwritten if arguments passed), log-directory validation.
4. **PHP ini** (`display_errors=0`, `html_errors=0`, `log_errors=0`,
   `zend.exception_ignore_args=0`, guarded by `function_exists('ini_set')`) then an
   unconditional **`error_reporting(E_ALL)`**.
5. **Strategy `initialize()` + `dispatch()`** — *before* handler registration and
   *before* the idempotence guard. Beware: for an asset/content sub-request
   (`?_tracy_bar=…`), `DevelopmentStrategy::dispatch()` serves it and **`exit`s** —
   on the first `enable()` this happens before any handler is registered, so
   `enable()` never returns and no shutdown handler runs; on a later
   `enable()`/`Debugger::dispatch()` the same path sets `$assetsSent`, which
   suppresses `renderBar()`.
6. **Idempotence guard** (`if ($enabled) return`).
7. **Handler registration, in this order:** `register_shutdown_function` **first**,
   `set_exception_handler` **second** (its closure always ends `exit(255)`),
   `set_error_handler` **third**.
8. `require_once` of Tracy's own classes (so a fatal that hits the autoloader — e.g.
   memory exhaustion — still finds them), then `$enabled = true`.

Steps 3–5 run on *every* `enable()` call (before the guard); handlers register once.

## Development vs Production strategy gates almost everything

`getStrategy()` keys on `(int)(bool)$productionMode` → `DevelopmentStrategy`
(gets Bar + BlueScreen + `DeferredContent`) or `ProductionStrategy` (logs + a
neutral 500 page). `detectDebugMode` whitelists `REMOTE_ADDR` (localhost only when
no proxy header, `secret@addr` via the `tracy-debug` cookie; without `REMOTE_ADDR`
it falls back to `php_uname('n')`). So whether an error renders or is merely logged
is decided entirely here — a common surprise in tests.

**The strategy is resolved lazily on every handler call** (`errorHandler`,
`exceptionHandler`, `shutdownHandler`, `dispatch`, `renderLoader` all call
`getStrategy()` at call time). Handlers register once, but behaviour follows a later
flip of `$productionMode`; flipping production → development after `enable()`
creates a fresh `DevelopmentStrategy` + `DeferredContent` whose `sendAssets()`
never ran, so deferral is silently off (see deferred-content.md).

`ProductionStrategy::initialize()` can itself consume the render guard: with
`ini_set` disabled and `display_errors` on it calls `Debugger::exceptionHandler()`
with a `RuntimeException` and returns, so a later real exception is no longer
"first".

## The handler flow

- **`exceptionHandler`** — `$reserved` doubles as a **double-render guard**
  (`$firstTime = (bool) $reserved; $reserved = null`); only the BlueScreen/500 page
  is guarded — a non-first exception in development still goes to
  `renderExceptionCli` (log + text). It runs `Helpers::improveException()` (mutates
  the message, see helpers.md), snapshots the ob status, sends HTTP 500,
  `removeOutputBuffers`, then delegates to `strategy->handleException`.
  `$onFatalError` runs only on the first exception. **The method itself never
  exits** — the `exit(255)` lives only in the closure registered via
  `set_exception_handler`. Both `shutdownHandler` (which must continue to free
  `$reserved` and render the Bar) and `enable()`'s log-dir failure path (which adds
  its own explicit `exit(255)`) rely on that.
- **`errorHandler`** — `E_RECOVERABLE_ERROR`/`E_USER_ERROR` become a thrown
  `ErrorException`; other errors are handled when `severity & error_reporting` **or**
  matched by the `$scream` mask; it **returns `false` on purpose** so PHP's native
  handler still fills `error_get_last()`. `$strictMode` is applied later, in
  `DevelopmentStrategy::handleError` (not here): it builds an `ErrorException`,
  flags it with a dynamic `$e->skippable` (BlueScreen renders the "skip error"
  action, which round-trips as the `?_tracy_skip_error` GET parameter), calls
  `exceptionHandler` and exits. In production `$strictMode` is unused —
  `$logSeverity` decides HTML-report vs plain-text log instead.
- **`E_COMPILE_WARNING` never reaches a PHP error handler.** Both `errorHandler`
  (at its top) and `shutdownHandler` pick it up from `error_get_last()`,
  `error_clear_last()` and re-enter `errorHandler`; a "simplification" that drops
  this loses the warning silently.
- **`shutdownHandler`** — catches fatals from `error_get_last()` (E_ERROR /
  E_PARSE / …), rebuilds an `ErrorException` (optionally grafting a trace by
  reflection), calls `exceptionHandler`, frees `$reserved`, and finally renders the
  Bar if `$showBar`. Because `$reserved` is freed **before** `renderBar()`, an
  exception thrown while rendering the Bar (a broken panel) is a non-first
  exception: development prints text, production only logs — never a BlueScreen.

`removeOutputBuffers` strips buffers above the recorded `$obLevel` and **stops** at
an `ob_gzhandler`/zlib buffer or at a buffer that refuses to close (buffers below it
stay). It uses `ob_end_clean` only when an error occurred **and** the buffer has no
`chunk_size`; a streaming buffer (non-zero `chunk_size`) is always flushed, even on
error — that is why streamed output is not discarded by a fatal.

`dispatch()` must run *after* `session_start()` for `NativeSession`, or deferral is
unavailable; the visible symptom is `Bar::renderLoader()` throwing "Start session
before Tracy is enabled." Since `DeferredContent::sendAssets()` returns quietly when
output has already started (unless an asset or an AJAX response is being served),
an `enable()`/`dispatch()` placed after output no longer throws — deferral is just
silently off for that request.
