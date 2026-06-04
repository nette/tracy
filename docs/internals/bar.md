# Bar

The debug toolbar is a collection of `IBarPanel`s rendered at shutdown
(`Debugger::shutdownHandler` → `DevelopmentStrategy::renderBar` → `Bar::render`),
after the response body. On an ordinary HTML page it is emitted **inline** and
needs no session; only AJAX, redirect and the `renderLoader()` path go through
`DeferredContent` (see deferred-content.md).

## Panels

`addPanel(IBarPanel $panel, ?string $id = null)` stores the panel under an id
auto-derived from its class (suffixed on collision). `renderPanels()` calls each
panel's `getTab()` and, only if the tab is non-empty, `getPanel()` — **in that
order**, which the built-in `info` panel depends on (its tab template stores
`$this->time`, its panel template reads it). Rendering runs under a temporary
error handler (errors within `error_reporting()` become `ErrorException`s,
`@`-silenced ones are swallowed) and unwinds output buffers on a throw — a
throwing panel is caught and replaced with an "Error in `<id>`" panel.
`renderAgent()` produces the markdown line `Tracy Bar | <ms> | <MB>` plus each
panel's *optional* `getAgentInfo()` (declared on `IBarPanel` only as `@method`,
probed via `method_exists`).

The built-in panels are `DefaultBarPanel`s backed by `.phtml` templates: `info` and
`warnings` (registered when the Bar is created; `warnings` is filled by
`errorHandler`), and `dumps` (registered lazily on the first `barDump()`).
**The ids `Tracy:info` and `Tracy:warnings` are load-bearing strings**:
`DevelopmentStrategy` fetches them by exact id and writes their public/dynamic
properties (`cpuUsage`, `$data`) from outside — `getPanel()` returns `null` for an
unknown id, so renaming a registration is a runtime fatal, not a graceful
degradation. `DefaultBarPanel` needs `#[\AllowDynamicProperties]` for the same
reason (and because its templates write dynamic properties themselves).

Panel element ids are `tracy-debug-panel-<id>` with a per-partial suffix
(`-ajax:<requestId>`, `-r<n>`) so that several AJAX/redirect rows can coexist in
the DOM; bar.js keys `Debug.panels[]` and tab `rel` attributes on exactly these
strings (see js-contract.md).

## Rendering is mode-dependent

`render(DeferredContent $defer)` branches on AJAX / redirect / normal HTML (the
session paths are described in deferred-content.md). On the normal-HTML path it
renders the `main` partial, drains the redirect queue into it, and then either
`addSetup('Tracy.Debug.init', …)` if the loader already ran, or calls
`Debugger::removeOutputBuffers(errorOccurred: false)` (flushing user buffers above
`$obLevel`) and `require`s `loader.phtml` directly. If a `Content-Length` header was
already sent (the injected markup would corrupt it), it only logs a
`LogicException` — rendering proceeds unchanged. In agent mode the markdown goes to
`Helpers::consoleLog()` directly on the inline path, and as
`addSetup('console.log', …)` / a queue item on the session paths.

`renderBar()` returns early in CLI and when `$assetsSent` (an asset sub-request
`exit`ed); `Debugger::$showBar` gates the whole thing in `shutdownHandler`.

`renderLoader()` requires an available session (else "Start session before Tracy is
enabled.") and emits an async loader `<script>` early in the page: it is the only
non-AJAX case where the main Bar travels through the session, and its purpose is
not to be held up by slow blocking scripts in the page — the `content.<id>` fetch
itself still waits on the session lock until the PHP request finishes.
