# Deferred content: surviving redirects & riding AJAX

The most counterintuitive mechanism in Tracy. The Bar and BlueScreen cannot always
render into the current response (a redirect has no body; an AJAX response is not
the page). `DeferredContent` bridges that gap through the **session**, and the
content is delivered by a **second HTTP request the browser makes for it**.

## The three defer paths (from `Bar::render`)

- **AJAX** → `addSetup('Tracy.Debug.loadAjax', <partial>)`. (The AJAX/deferred flag
  is decided once in the `DeferredContent` constructor — `X-Tracy-Ajax` header
  matching `^\w{10,15}$` — `Bar::render` only checks `isDeferred()`.)
- **Redirect** (a `Location:` header is present) → nothing is emitted; the content is
  pushed onto a `redirect` queue in the session.
- **Normal HTML** → the main partial is rendered *and the redirect queue is drained*
  (reversed, appended, then `null`ed), so content accumulated during prior redirects
  finally appears on the next real page. BlueScreen's AJAX path is analogous:
  `addSetup('Tracy.BlueScreen.loadAjax', <html>)`.

## `addSetup` writes JS into the session; the browser fetches it back

`addSetup($method, $arg)` appends `"$method($arg);\n"` to
`getItems('setup')[$requestId]['code']` — and `getItems` returns a **reference into
the session data**, so the write lands directly in the session. The request that
*produces* debug output stores it under its own `requestId`; the browser then makes
a separate `GET ?_tracy_bar=content.<requestId>` (or `content-ajax.<id>`), which
`dispatch()`/`sendAssets()` answers by pulling the stored `code` out of the session,
**`unset`ting it (one-time consumption)**, and returning it as JavaScript. That is
how a redirect's Bar shows up after the redirect completes.

`?_tracy_bar=js` serves the merged static assets once with a long `Cache-Control`
(the CSS is minified, the JS only IIFE-wrapped and concatenated). `clean()` keeps
only the last 10 items per key and only those younger than 60 seconds — and it runs
inside `sendAssets()` *before* the content fetch is answered, so a payload older
than 60 s is gone by the time the browser asks for it. **Every item stored in the
session must carry a `time` key**, or `clean()` silently discards it (`addSetup`
and the redirect push both stamp `time()`).

**Ordering invariant:** `isAvailable()` is `$useSession && sessionStorage->isAvailable()`,
and `$useSession` is set **only inside `sendAssets()`**. So all deferral works only
because `dispatch()` → `sendAssets()` runs early in `enable()`; drop that call and
every `isAvailable()` gate in Bar/BlueScreen goes false, silently disabling deferral.

## `FileSession` locking is coarse — and that is a trap

The default `FileSession` (cookie `tracy-session`, file `tracy-<id>`) takes a
**blocking `flock(LOCK_EX)`** on first access and **holds it for the entire
request**, writing and unlocking only in `__destruct`. Consequences to respect:

- concurrent requests sharing the cookie (an AJAX call plus the main page)
  **serialize** — they block each other;
- a crash without a clean shutdown **loses** the pending writes (no truncate/write);
- `isAvailable()` is **not** a read-only probe — it opens and locks the file.

`FileSession` also has its own file GC, unrelated to `clean()`: session files older
than a week are deleted with probability 0.03 on open. `NativeSession` stores under
`$_SESSION['_tracy']` and is available only when a PHP session is active.
