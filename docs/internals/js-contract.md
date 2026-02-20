# The PHP ↔ JS contract

Tracy's client side (`bar.js`, `bluescreen.js`, `dumper.js`, `toggle.js`, …) and
its PHP side are coupled **entirely by strings** — function names, element ids,
attribute names, storage keys. Nothing checks the two sides against each other;
renaming either side breaks the other silently at runtime. This file lists the
load-bearing names.

## Entry points: PHP emits JS calls as text

`DeferredContent::addSetup($method, $argument)` appends the literal source
`"$method($argument);\n"` to the session; the browser executes it later. The
`$method` strings must match symbols the JS bundle defines on `window.Tracy`:

| PHP emission | JS definition |
|---|---|
| `addSetup('Tracy.Debug.init', …)` (Bar) + inline in `loader.phtml` | `Debug.init` (bar.js) |
| `addSetup('Tracy.Debug.loadAjax', {bar, panels})` (Bar) | `Debug.loadAjax` (bar.js) |
| `addSetup('Tracy.BlueScreen.loadAjax', <html>)` (BlueScreen) | `BlueScreen.loadAjax` (bluescreen.js) |
| `Tracy.BlueScreen.init()` inline in `page.phtml` | `BlueScreen.init` (bluescreen.js) |
| `addSetup('console.log' / 'console.error', <markdown>)` — agent mode | browser built-ins |

`Tracy.Debug.loadAjax` expects an **object** `{bar, panels}` of HTML strings; the
others take a single HTML string. Inline `<script>`s inside injected HTML
fragments are re-executed by `evalScripts` (empty/`text/javascript` types only).

## The requestId round-trip

One 10-char hex id (`Helpers::createId()` = `bin2hex(random_bytes(5))`) threads
the whole deferral loop as a plain string with no shared schema:

1. PHP puts it in the loader tag:
   `<script src="…?_tracy_bar=content.<id>…" data-id="<id>">`.
2. bar.js reads `document.currentScript.dataset.id`; AJAX ids are
   `Tracy.getAjaxHeader()` = `<id>_<counter>`.
3. Monkeypatched `XMLHttpRequest.prototype.open` / `window.fetch` attach it as
   the `X-Tracy-Ajax` request header on same-host requests (gated by the
   `TracyAutoRefresh` global).
4. PHP accepts any header value matching `^\w{10,15}$` as the requestId
   (`DeferredContent` constructor — `\w` covers the `_` in the counter format)
   and stores the setup code under it; it marks the response with
   `X-Tracy-Ajax: 1`.
5. The JS sees the `1` and fetches `?_tracy_bar=content-ajax.<id>`, which returns
   the stored code (`content.<id>` — the initial loader form — additionally
   prepends the js/css bundle).

The `^\w{10,15}$` regex, the `_<counter>` suffix format, and the
`content(-ajax).` prefixes must stay compatible on both sides.

## Dump data: attributes and the snapshot meta tag

dumper.js consumes exactly:

- `[data-tracy-snapshot][data-tracy-dump]` — a fully-lazy dump, both JSON.
- `meta[itemprop=tracy-snapshot]` — the shared snapshot for sibling
  `[data-tracy-dump]` elements (Bar `panels.phtml`, BlueScreen `content.phtml`);
  deliberately left in the DOM after processing.
- Nested `[data-tracy-dump]` on collapsed `tracy-toggle` spans — expanded lazily
  on the `tracy-beforetoggle` event.
- `{ref: id}` nodes resolve against the snapshot; a missing id **throws**
  (`UnknownEntityException`) — the reachable-slice logic in `Renderer`
  (`snapshotSelection`) exists precisely to keep every emitted ref resolvable.
- `data-tracy-href` — Ctrl/Cmd-click navigation (editor links).

Toggling: the `toggle.js` contract is class `tracy-toggle` + class
`tracy-collapsed` + an optional `data-tracy-ref` mini-selector (`^` = parent /
`closest`, `+` = next sibling, `#…` = document scope). It fires bubbling
`tracy-beforetoggle` / `tracy-toggle` CustomEvents that bar.js, bluescreen.js and
dumper.js listen to — the event bus between the modules.

## Other load-bearing names

- Element ids: `tracy-debug-bar`, `tracy-debug`, `tracy-bs`, `tracy-bs-toggle`;
  the host element is `<tracy-div>` (CSS isolation — no Shadow DOM).
- Storage: localStorage `tracy-debug-bar` + a key per panel id (positions);
  sessionStorage `tracy-toggles-tracy-bs` and `tracy-toggles-bskey` (BlueScreen
  toggle persistence); cookie `tracy-webdriver=1` (session cookie set by bar.js
  when `navigator.webdriver`, read by `Helpers::isAgent()`).
- JS options are plain globals: `TracyAutoRefresh`, `TracyMaxAjaxRows`,
  `TracyPanelZIndex`.
- Class contracts queried by JS: `.tracy-panel`,
  `.tracy-row[data-tracy-group=ajax]`, `.tracy-tabs` / `.tracy-tab-label` /
  `.tracy-tab-panel` / `.tracy-active`, `table.tracy-sortable` (per-cell
  `data-order` override), `.tracy-section--error`.

## CSP nonce

`Helpers::getNonce()` regex-scans the **already-sent** headers (`headers_list()`)
for a `Content-Security-Policy(-Report-Only)` header with a
`script-src(-elem) 'nonce-…'` directive and returns the nonce — or `null`, in
which case no `nonce` attribute is emitted at all. **The CSP header must
therefore be sent before Tracy renders anything.** The nonce is re-read
independently at every emission point (loader, `Dumper::renderAssets`, BlueScreen
page, `error.500`, `consoleLog`); the `<style>` injected by the js/css bundle
reads it off `document.currentScript` at runtime instead.

## `jsonEncode()`'s `$inScript` flag

All JSON crossing the boundary goes through `Helpers::jsonEncode()`. It always
hex-escapes `'` and `&` (which is what makes the single-quoted `data-tracy-*`
attributes safe), but escapes `<`/`>` **only with `inScript: true`** — forgetting
the flag inside a `<script>` body enables `</script>` breakout.
