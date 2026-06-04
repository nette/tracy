# Deferred content: surviving redirects & riding AJAX

The most counterintuitive mechanism in Tracy. The Bar cannot always render into
the current response (a redirect has no body; an AJAX response is not the page).
`DeferredContent` bridges that gap through the **session**. Note the ordinary Bar
on a normal HTML page does **not** go through it at all: `Bar::render` emits it
inline via `loader.phtml` and needs no session (see bar.md).

## The three paths of `Bar::render`

- **AJAX** → `addSetup('Tracy.Debug.loadAjax', <partial>)`. (The AJAX/deferred flag
  is decided once in the `DeferredContent` constructor from the `X-Tracy-Ajax`
  request header — `Bar::render` only checks `isDeferred()`.) The browser then
  fetches `?_tracy_bar=content-ajax.<id>`.
- **Redirect** (a `Location:` header is present) → nothing is emitted; the partial is
  pushed onto a `redirect` queue in the session.
- **Normal HTML** → the main partial is rendered *and the redirect queue is drained*
  into it (reversed, appended, then `null`ed — the queue is a by-reference session
  item, so draining is a persistent session mutation). Content accumulated during
  prior redirects thus appears on the next real page, **server-side, inline** — no
  fetch is involved unless `renderLoader()` was used earlier, in which case the whole
  Bar goes through `addSetup('Tracy.Debug.init', …)` and the browser fetches
  `?_tracy_bar=content.<id>`.

Both session paths are gated by `isAvailable()`; without a usable session the Bar
of that request is silently dropped, not errored. BlueScreen has **no** redirect
path — only its AJAX form is deferred (`renderToAjax` →
`addSetup('Tracy.BlueScreen.loadAjax', <html>)`); a normal-page BlueScreen renders
inline with HTTP 500.

## `addSetup` writes JS into the session; the browser fetches it back

`addSetup($method, $arg)` appends `"$method($arg);\n"` to
`getItems('setup')[$requestId]['code']` — and `getItems` returns a **reference into
the session data**, so the write lands directly in the session. The browser's
`GET ?_tracy_bar=content(-ajax).<requestId>` is answered by `dispatch()` →
`sendAssets()`, which pulls the stored `code` out of the session, **`unset`s it
(one-time consumption)**, and returns it as JavaScript (`content.` additionally
prepends the js/css bundle; `?_tracy_bar=js` serves the bundle alone). Both
responses `header_remove('Set-Cookie')`, so a `tracy-session` cookie created inside
such a fetch is never sent.

`clean()` keeps only the last 10 items per key and only those younger than 60
seconds — and it runs inside `sendAssets()` on every request that reaches the
session step, so a payload older than 60 s is gone by the time the browser asks
for it. **Every item stored in the session must carry a `time` key**, or `clean()`
silently discards it (`addSetup` and the redirect push both stamp `time()`).
Session items must be plain arrays/scalars: `FileSession` unserializes with
`allowed_classes => false`.

**Ordering invariant:** `isAvailable()` is `$useSession && sessionStorage->isAvailable()`,
and `$useSession` is set **only inside `sendAssets()`**. So all deferral works only
because `dispatch()` → `sendAssets()` runs early in `enable()`; drop that call and
every `isAvailable()` gate in Bar/BlueScreen goes false, silently disabling deferral.
`sendAssets()` also **returns early, without setting `$useSession`, when output has
already started** (it throws only if an asset or an AJAX response is being served
at that point) — a late `enable()` therefore disables deferral quietly. For
`NativeSession`, `Debugger::dispatch()` must run after `session_start()`.

## `FileSession` locking is coarse — and that is load-bearing

The default `FileSession` (cookie `tracy-session`, file `tracy-<id>`, `chmod 0600`)
takes a **blocking `flock(LOCK_EX)`** on first access and **holds it for the entire
request**, writing and unlocking only in `__destruct`. That is not merely a trap:
`sendAssets()` locks the session (via `isAvailable()`) *before* it sends the
`X-Tracy-Ajax: 1` marker and before the loader tag is emitted, so the browser's
`content(-ajax).<id>` fetch **blocks on the lock until the producing request has
written the session**. Narrow the lock to the read/write and the fetch may find an
empty `code` — the Bar simply vanishes. Consequences to respect:

- concurrent requests sharing the cookie (an AJAX call plus the main page)
  **serialize** — they block each other;
- a crash without a clean shutdown **loses** the pending writes (no truncate/write);
- `isAvailable()` is **not** a read-only probe — it opens and locks the file, and it
  never returns `false`: it either locks or **throws** `RuntimeException` (reached
  from `enable()`, so an unwritable temp dir fails at enable time). Only
  `NativeSession::isAvailable()` can return `false` (no active PHP session).
- `getData()` does not open the file, only `isAvailable()` does, and `open()`
  reassigns `$data` — a reference obtained via `getItems()` *before* the first
  `isAvailable()` points at a stale array. Today this is safe only because
  `sendAssets()` runs in `enable()` before any `getItems()`.

`FileSession` also has its own file GC (probabilistic, on open), unrelated to
`clean()`. `NativeSession` stores under `$_SESSION['_tracy']`.
