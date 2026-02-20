# BlueScreen

`render()` builds the HTML error page from `page.phtml`; `renderToAjax` defers it
(`addSetup('Tracy.BlueScreen.loadAjax', …)`), `renderToFile` writes it with
`fopen(…, 'x')` (so an existing file is never overwritten) plus a `.md` companion,
and `renderAgent` produces the markdown variant. `renderTemplate` is the shared core
that assembles headers, CSS/JS assets, the dumpers, and a **live shared snapshot**
(`$this->snapshot = []; $snapshot = &$this->snapshot[0]`) before `require`-ing the
template.

## Panels are callbacks, called repeatedly

`addPanel(callable)` stores a `Closure(?Throwable): ?array{tab, panel}`. It is
invoked **multiple times with different arguments** during a render:

- once **per exception in the chain** (`section-exception.phtml` is re-`require`d
  for every `getPrevious()` link, each time calling `renderPanels($ex)`),
- plus once **with `null`** (below the call stack, `content.phtml`).

So a chain of N exceptions means N+1 invocations. A panel wanting to appear at the
very bottom returns `bottom: true`, which defers it to `$bottomPanels`. A panel
callback must tolerate both a `Throwable` and `null`, and is responsible for
rendering the right thing in each pass. Empty tab/panel results are skipped; a
throwing panel becomes an "Error in panel" block. (Separate from panels:
`addAction`, `addFileGenerator`, `addFiber`.)

## Stack, highlighting, and the two dumpers

`prepareStack` strips Tracy's own frames (`DevelopmentStrategy`/`ProductionStrategy`,
`Debugger::shutdownHandler`/`errorHandler`) from the trace and returns
`[$stack, $expanded]` — the index of the single frame to auto-expand, computed from
`Debugger::$transparentPaths` (the deprecated `$collapsePaths` is still merged in,
so it remains functionally live); the `tracy-collapsed` class itself is applied in
the template by comparing against that index. `CodeHighlighter` tokenizes with
`\PhpToken`, maps tokens to CSS classes, shows ~15 lines around the error,
highlights the line and column, and replaces `/*sensitive{*/…/*}*/` regions with
`*****` (`Describer::HiddenValue`) before highlighting (PHP path only, not the
plain-text one).

Two dumpers exist: `getDumper()` renders **HTML** (`maxDepth` — default 5,
`maxLength`/`maxItems`, `LOCATION_CLASS`, the shared `SNAPSHOT`, scrubber,
`keysToHide`) and feeds the page templates; `getAgentDumper()` renders
**text/markdown** (hardcoded depth 3, no snapshot, no location) and feeds only the
`agent.phtml` markdown variant. Note `keysToHide` includes `BlueScreen::$snapshot`
itself, so the internal snapshot never leaks into a dump.

**Ordering invariant:** the shared snapshot is populated by reference *while* the
template renders each dump; its serialized form is written only at the very end of
`content.phtml` into `<meta itemprop=tracy-snapshot …>`. Moving that meta tag
before the dumps (or dumping after it) silently breaks collapsed-dump expansion on
the client. `renderTemplate` resets `$this->snapshot` both before and after the
`require`, so dumps outside that window are not captured.
