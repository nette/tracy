# Debugger bootstrap & error handling

`Debugger::enable()` wires PHP's error machinery; the ordering is the non-local
knowledge.

## `enable()` order

1. **Mode gate** — sets `$productionMode` (an explicit bool is used directly, else
   `!detectDebugMode($mode)`), but **only** when a `$mode` argument is passed or the
   mode is still `Detect` — a repeated `enable()` without `$mode` does *not*
   re-evaluate an already-resolved mode.
2. **Reserve memory / record `$time` / record `$obLevel`.** Note **`ob_start()` is
   never called** — Tracy does *not* run its own output buffer; it only remembers
   the buffer level at enable time and later strips buffers *above* it
   (`removeOutputBuffers`).
3. Logging config (only overwritten if arguments passed), log-directory validation.
4. **PHP ini** (`display_errors=0`, `html_errors=0`, `log_errors=0`,
   `zend.exception_ignore_args=0`) then **`error_reporting(E_ALL)`**.
5. **Strategy init + `dispatch()`** — *before* handler registration and *before* the
   idempotence guard. Beware: for an asset/content sub-request
   (`?_tracy_bar=…`), `DevelopmentStrategy::dispatch()` serves it and **`exit`s** —
   `enable()` may never return; the same path sets `$assetsSent`, which suppresses
   `renderBar()`.
6. **Idempotence guard** (`if ($enabled) return`).
7. **Handler registration, in this order:** `register_shutdown_function` **first**,
   `set_exception_handler` **second** (its closure always ends `exit(255)`),
   `set_error_handler` **third**.
8. `require_once` the internal classes, then `$enabled = true`.

**A subtlety:** the ini/`error_reporting` block runs on *every* `enable()` call
(before the guard), but the handlers register only once.

## Development vs Production strategy gates almost everything

`getStrategy()` keys on `(int)(bool)$productionMode` → `DevelopmentStrategy`
(gets Bar + BlueScreen + `DeferredContent`) or `ProductionStrategy` (logs + a
neutral 500 page). `detectDebugMode` whitelists `REMOTE_ADDR` (localhost only when
no proxy header, `secret@addr` via the `tracy-debug` cookie). So whether an error
renders or is merely logged is decided entirely here — a common surprise in tests.

## The handler flow

- **`exceptionHandler`** — `$reserved` doubles as a **double-render guard**
  (`$firstTime = (bool) $reserved; $reserved = null`). It snapshots the ob status,
  sends HTTP 500, `removeOutputBuffers`, then delegates to
  `strategy->handleException`. `$onFatalError` runs only on the first exception.
  **The method itself never exits** — the `exit(255)` lives only in the closure
  registered via `set_exception_handler`. Both `shutdownHandler` (which must
  continue to free `$reserved` and render the Bar) and `enable()`'s log-dir
  failure path (which adds its own explicit `exit(255)`) rely on that.
- **`errorHandler`** — `E_RECOVERABLE_ERROR`/`E_USER_ERROR` become a thrown
  `ErrorException`; other errors are handled when `severity & error_reporting` **or**
  `$scream` is set; it **returns `false` on purpose** so PHP's native handler still
  fills `error_get_last()`. `$strictMode` is applied later, in
  `DevelopmentStrategy::handleError` (not here); in production it is unused —
  `$logSeverity` decides HTML-report vs plain-text log instead.
- **`shutdownHandler`** — catches fatals from `error_get_last()` (E_ERROR /
  E_PARSE / …), rebuilds an `ErrorException` (optionally grafting a trace by
  reflection), calls `exceptionHandler`, frees `$reserved`, and finally renders the
  Bar if `$showBar`.

`removeOutputBuffers` strips buffers above the recorded `$obLevel`, skipping
`ob_gzhandler`/zlib compression. It uses `ob_end_clean` only when an error occurred
**and** the buffer has no `chunk_size`; a streaming buffer (non-zero `chunk_size`)
is always flushed, even on error — that is why streamed output is not discarded by
a fatal. The Bar stays addable until it is rendered (at shutdown);
`dispatch()` must run *after* `session_start()` for `NativeSession`, or deferral is
unavailable.
