# BlueScreen

`render()` builds the HTML error page from `page.phtml`; `renderToAjax` defers the
body-only `content.phtml` (`addSetup('Tracy.BlueScreen.loadAjax', …)`, relying on
the Bar's asset bundle for CSS/JS), `renderToFile` writes the page with
`fopen(…, 'x')` (so an existing file is never overwritten) plus a `.md` companion,
and `renderAgent` produces the markdown variant. `renderTemplate` is the shared core
that collects headers, CSS/JS assets, the dumpers, and the **shared snapshot**
before `require`-ing the template; it resets `$this->snapshot` both before and
after the `require`, so dumps outside that window are not captured. `page.phtml`
itself re-enters it (`renderAgent()` for agents, inline `console.error`), which is
safe only because that happens *after* `content.phtml` has emitted the snapshot.

**Nothing may trigger autoloading while rendering**: `formatMessage()` and
`renderActions()` use `class_exists(…, autoload: false)` and friends; keep it that
way in templates and panels — an autoloader firing inside an error page is how a
BlueScreen turns into a white page.

## Panels are callbacks, called repeatedly

`addPanel(callable)` stores a `Closure(?Throwable): ?array{tab, panel}`. It is
invoked **multiple times with different arguments** during a render:

- once **per distinct exception in the chain** (`section-exception.phtml` recurses
  through `section-exception-causedBy.phtml` with a cycle guard, each time calling
  `renderPanels($ex)`),
- plus once **with `null`** (below the whole chain, `content.phtml`).

So a chain of N exceptions means N+1 invocations. A panel callback must tolerate
both a `Throwable` and `null`, and is responsible for rendering the right thing in
each pass. The `bottom` and `collapsed` keys are honoured **only in the `null`
pass** (`bottom: true` defers to `$bottomPanels`; null-pass panels are collapsed by
default); in the per-exception passes the panel is rendered expanded right after
the exception header. Empty tab/panel results are skipped; a throwing panel becomes
an "Error in panel" block. `addPanel` deduplicates only the same Closure instance.
(Separate from panels: `addAction`, `addFileGenerator`, `addFiber`.)

## Stack, highlighting, and the two dumpers

`prepareStack` strips Tracy's own *leading* handler frames from the trace and
returns `[$stack, $expanded]` — the index of the single frame to auto-expand,
computed from `Debugger::$transparentPaths` (the deprecated `$collapsePaths` is still
merged in, so it remains functionally live) and `@tracySkipLocation`; for an
`ErrorException` other than `E_USER_NOTICE/WARNING/DEPRECATED` it is always `null` (source file expanded,
all frames collapsed). The `tracy-collapsed` class itself is applied in the
template by comparing against that index. `CodeHighlighter` tokenizes PHP, shows
`DisplayLines` (15) lines around the error — the public `$lines` parameters of the
`highlight*` methods are ignored — highlights the line and column, and replaces
`/*sensitive{*/…/*}*/` regions with `*****` (`Describer::HiddenValue`) before
highlighting (PHP path only, not the plain-text one).

Two dumpers exist: `getDumper()` renders **HTML** (`BlueScreen::$maxDepth` etc.,
`LOCATION_CLASS`, the shared `SNAPSHOT`, scrubber, `keysToHide`) and feeds the page
templates; `getAgentDumper()` renders **text/markdown** (depth 3, no snapshot, no
location, but the same scrubber and `keysToHide`) and feeds only the `agent.phtml`
markdown variant. Note `keysToHide` includes `BlueScreen::$snapshot` itself, so the
internal snapshot never leaks into a dump. **BlueScreen limits are independent of
`Debugger::$maxDepth/$maxLength/$maxItems/$keysToHide`** (`Debugger::dumpOptions()`
feeds `dump()` only); `TracyExtension` pushes `keysToHide` into both, but depth and
length only into `Debugger`.

**Ordering invariant:** the shared snapshot is populated by reference *while* the
template renders each dump; its serialized form is written only at the very end of
`content.phtml` into `<meta itemprop=tracy-snapshot …>`. Moving that meta tag
before the dumps (or dumping after it) silently breaks collapsed-dump expansion.
dumper.js applies the snapshot to `meta.parentElement`'s subtree, so the meta must
also stay a direct child of `<tracy-div id=tracy-bs>`.
