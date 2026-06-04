# Helpers gotchas

Cross-cutting traps in `Helpers.php`; everything not listed here is clear from
the signatures.

- **`improveException()` mutates the exception.** For an `\Error`/`\ErrorException`
  it rewrites the private `$message` by reflection (replacing or appending a ", did
  you mean …?" hint) and may set a dynamic `$e->tracyAction` property
  (`{link, label}`) that `BlueScreen::renderActions()` reads. It is idempotent (a
  message already containing the hint is left alone), which matters because every
  uncaught exception passes through it in `Debugger::exceptionHandler` and again in
  `ProductionStrategy::handleError`. Suggestions come from a weighted Levenshtein
  (`getSuggestion`), not a plain edit distance.
- **`editorUri()` remaps paths through `Debugger::$editorMapping`** (`strtr`)
  before substituting the `%file`/`%line`/`%action`/… placeholders into
  `Debugger::$editor`. The same mapping is applied to the display text in
  `editorLink()` and to the `$browser` exec path in `DevelopmentStrategy`. Returns
  `null` when `$editor` is unset or the file does not exist (except `action: 'create'`).
- **"Dumped from" locations can skip frames silently.** `findCallerLocation()`
  ignores frames located inside a function whose docblock contains
  `@tracySkipLocation`, frames without a real file (eval), and frames under
  `Debugger::$transparentPaths` — whose default (`detectTransparentPaths()`) is
  `vendor/tracy`, `vendor/nette`, `vendor/latte` when Tracy sits in `vendor/`, else
  Tracy's own `src/Tracy`, so behaviour differs inside and outside a `vendor/`
  install.
- **`capture()` swallows output**: nothing echoed inside it can ever reach the
  response, even if the buffer is force-flushed later; on a throw it cleans the
  buffer and rethrows. Most template rendering goes through it.
- **`isHtmlMode()` is the global "may I inject into this response" gate** —
  false on AJAX (`X-Requested-With` / `X-Tracy-Ajax`), CLI, a missing
  `HTTP_HOST`, or a non-`text/html` `Content-Type` header already *set* via
  `header()` (sent or not).
- **`isAgent()` is the second global gate**, switching output to markdown. It is
  true for the `tracy-webdriver=1` cookie (set, and otherwise actively cleared, by
  `bar.js` from `navigator.webdriver`) **or** any `X-Tracy-Agent` request header.
  In HTML mode the markdown goes to the JS console (`consoleLog()`, and through
  `DeferredContent` on the AJAX/redirect paths); for a non-HTML, non-CLI response it
  becomes the response body (`DevelopmentStrategy::renderExceptionCli` →
  `BlueScreen::renderAgent`).
