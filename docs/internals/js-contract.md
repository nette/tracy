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
| `addSetup('console.log' / 'console.error', <markdown>)` — deferred agent paths; the inline paths use `Helpers::consoleLog()` and `page.phtml`'s own `console.error` | browser built-ins |

`Tracy.Debug.loadAjax` expects an **object** `{bar, panels}` of HTML strings; the
others take a single HTML string. Inline `<script>`s inside injected HTML
fragments are executed by `evalScripts` — at most once each (`tracyEvaluated`
flag, since it re-runs on every init/loadAjax), and only scripts with no `type`
attribute or a JavaScript MIME type (a present but empty `type=""` is skipped).

## Asset endpoints and the requestId round-trip

`DeferredContent::sendAssets()` answers `?_tracy_bar=js` (the bundle; also fetched
by the sync loader and by the panel popup `Panel.toWindow()`), `content.<id>`
(bundle + stored code) and `content-ajax.<id>` (stored code only). One 10-char hex
id (`Helpers::createId()`) threads the deferral loop as a plain string with no
shared schema:

1. PHP puts it in the loader tag:
   `<script src="…?_tracy_bar=content.<id>…" data-id="<id>">`.
2. bar.js reads `document.currentScript.dataset.id`; without it
   `Debug.captureAjax()` is a no-op. AJAX ids are `Tracy.getAjaxHeader()` =
   `<id>_<counter>`.
3. Monkeypatched `XMLHttpRequest.prototype.open` / `window.fetch` attach it as
   the `X-Tracy-Ajax` request header on same-host requests (gated by the
   `TracyAutoRefresh` global; a caller-supplied `X-Tracy-Ajax` is respected).
4. PHP accepts any header value matching `^\w{10,15}$` as the requestId
   (`DeferredContent` constructor — `\w` covers the `_` in the counter format)
   and stores the setup code under it; iff the session is usable it marks the
   response with `X-Tracy-Ajax: 1`.
5. The JS sees the `1` and fetches `?_tracy_bar=content-ajax.<id>`.

The `^\w{10,15}$` regex, the `_<counter>` suffix format, and the
`content(-ajax).` prefixes must stay compatible on both sides.

Agent detection is the second header protocol: `Helpers::isAgent()` is true for the
`tracy-webdriver=1` cookie (set or cleared by bar.js from `navigator.webdriver`)
**or** an `X-Tracy-Agent` request header; a non-HTML response for an agent carries
the markdown in its body and an `X-Tracy-Error-Log: <file>` response header
(`DevelopmentStrategy::renderExceptionCli`). `?_tracy_skip_error` is the GET
parameter behind BlueScreen's "skip error" action (`DevelopmentStrategy::handleError`).

## Dump data: attributes and the snapshot meta tag

dumper.js consumes exactly:

- `[data-tracy-snapshot][data-tracy-dump]` — a fully-lazy dump, both JSON.
- `meta[itemprop=tracy-snapshot]` — the shared snapshot for every
  `[data-tracy-dump]` in the meta's **parent's subtree** (Bar `panels.phtml`,
  BlueScreen `content.phtml`); deliberately left in the DOM after processing,
  because Bar panel content is injected later by `Panel.init` and re-scanned.
  In `panels.phtml` the meta must stay the **last child** of the `itemscope`
  wrapper — `Panel.toWindow()` copies `parentElement.lastElementChild` into the
  popup as the snapshot.
- Nested `[data-tracy-dump]` on collapsed `tracy-toggle` spans — expanded lazily
  on the `tracy-beforetoggle` event.
- `{ref: id}` nodes resolve against the snapshot; an unresolvable ref means that
  dump is skipped at init (`Dumper.init` is fault-tolerant per dump) or breaks lazy
  expansion — the reachable-slice logic in `Renderer::copySnapshot()` exists
  precisely to keep every emitted ref resolvable.
- `data-tracy-href` — Ctrl/Cmd-click navigation (editor links).

Toggling: the `toggle.js` contract is class `tracy-toggle` + class
`tracy-collapsed` + an optional `data-tracy-ref` mini-selector (`^` = parent /
`closest`, `+` = next sibling, `#…` = document scope; falls back to `href`, and
`#`/empty means `+`). It fires bubbling `tracy-beforetoggle` / `tracy-toggle`
CustomEvents that bar.js, bluescreen.js and dumper.js listen to — the event bus
between the modules.

## Other load-bearing names

- Element ids: `tracy-debug-bar`, `tracy-debug`, `tracy-bs`, `tracy-bs-toggle`;
  the host element is `<tracy-div>` (CSS isolation — no Shadow DOM).
- Panel wiring (`panels.phtml` ↔ bar.js): `.tracy-panel` with `data-tracy-content`
  (HTML injected by `Panel.init`, entity-escaped, not JSON) and
  `id="tracy-debug-panel-<id><suffix>"`; the tab's `rel` carries the same id
  (`Debug.panels[link.rel]`); suffixes `-ajax:<requestId>` / `-r<n>` (the JS strips
  `:…` for the localStorage key); `data-tracy-action` = `close|window`;
  `data-tracy-group` = `main|redirect|ajax`; class `tracy-panel-persist` enables
  toggle persistence; `.tracy-row`/`.tracy-label` drive `Bar.autoHideLabels`.
- Storage: localStorage `tracy-debug-bar` + a key per panel id (positions);
  sessionStorage `tracy-toggles-<baseEl.id>` (`Toggle.persist`; BlueScreen and
  persistent Bar panels) and `tracy-toggles-bskey`.
- JS options are plain globals read as `window['Tracy' + key]` (`AutoRefresh`,
  `MaxAjaxRows`, `PanelZIndex`).
- Class contracts queried by JS: `.tracy-row[data-tracy-group=ajax]`,
  `.tracy-tabs` / `.tracy-tab-label` / `.tracy-tab-panel` / `.tracy-active`,
  `table.tracy-sortable`, `.tracy-section--error`.

## CSP nonce

`Helpers::getNonce()` regex-scans the **already-set** headers (`headers_list()`)
for a `Content-Security-Policy(-Report-Only)` header with a
`script-src(-elem) 'nonce-…'` directive and returns the nonce — or `null`, in
which case no `nonce` attribute is emitted at all. **The CSP header must
therefore be sent before Tracy renders anything.** The nonce is re-read
independently at every emission point (loader, `Dumper::renderAssets`, the
BlueScreen page when rendered to screen, `error.500`, `consoleLog`); the `<style>`
injected by the js/css bundle reads it off `document.currentScript` at runtime
instead.

## `jsonEncode()`'s `$inScript` flag

All JSON crossing the boundary goes through `Helpers::jsonEncode()`. It always
hex-escapes `'` and `&` but **never `"`** — which is why every `data-tracy-*`
attribute carrying it must be single-quoted — and escapes `<`/`>` **only with
`inScript: true`**: forgetting the flag inside a `<script>` body enables
`</script>` breakout.
