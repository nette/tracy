# Bar

The debug toolbar is a collection of `IBarPanel`s rendered **after the response
body**, through `DeferredContent` (see deferred-content.md).

## Panels

`addPanel(IBarPanel $panel, ?string $id = null)` stores the panel under an id
auto-derived from its class (suffixed `-2`, `-3`… on collision). Note the
**panel**, not the Bar, carries `getTab()`/`getPanel()` — `Bar` exposes only
`getPanel($id)`. `renderPanels()` calls each panel's `getTab()` and, only if the tab
is non-empty, `getPanel()`; it wraps rendering in a temporary error handler
(errors become `ErrorException`s) and unwinds output buffers on a throw — a
throwing panel is caught and replaced with an "Error in `<id>`" panel.
`renderAgent()` produces the markdown line `Tracy Bar | <ms> | <MB>` plus each
panel's *optional* `getAgentInfo()` (probed via `method_exists`; it is not part of
the `IBarPanel` contract).

The built-in panels are `DefaultBarPanel`s backed by `.phtml` templates: `info` and
`warnings` (registered when the Bar is created; `warnings` is filled by
`errorHandler`), and `dumps` (registered lazily on the first `barDump()`).
**The ids `Tracy:info` and `Tracy:warnings` are load-bearing strings**:
`DevelopmentStrategy` fetches them by exact id and writes their public/dynamic
properties (`cpuUsage`, `$data`) from outside — `getPanel()` returns `null` for an
unknown id, so renaming a registration is a runtime fatal, not a graceful
degradation. `DefaultBarPanel` needs `#[\AllowDynamicProperties]` for the same
reason.

## Rendering is deferred and mode-dependent

`render(DeferredContent $defer)` branches:

- **AJAX/deferred** → `addSetup('Tracy.Debug.loadAjax', renderPartial('ajax'))`.
- **Redirect** → push the partial onto the session `redirect` queue.
- **Normal HTML** → render the `main` partial, **drain the redirect queue** (reverse
  order, then set to `null` — the queue is a by-reference session item, so draining
  is a persistent session mutation) so Bars from prior redirects appear now, then
  either `addSetup('Tracy.Debug.init', …)` if the loader already ran, or `require`
  `loader.phtml` directly. If a `Content-Length` header was already sent (the
  injected markup would corrupt it), it only logs a `LogicException` — rendering
  proceeds unchanged.

`renderLoader()` requires an available session (else "Start session before Tracy is
enabled.") and emits the loader early so the toolbar can appear even when the rest
of the page is slow.
