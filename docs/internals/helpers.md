# Helpers gotchas

Cross-cutting traps in `Helpers.php`; everything not listed here is clear from
the signatures.

- **`improveException()` mutates the exception.** It rewrites the private
  `$message` by reflection to append ", did you mean …?" and may set a dynamic
  `$e->tracyAction` property (`{link, label}`) that `BlueScreen::renderActions()`
  reads. Suggestions come from a weighted Levenshtein (`getSuggestion`), not a
  plain edit distance.
- **`editorUri()` remaps paths through `Debugger::$editorMapping`** (`strtr`)
  before substituting `%file`/`%line`/`%action`/… into `Debugger::$editor`. The
  same mapping is applied to the display text in `editorLink()` and to the
  `$browser` exec path in `DevelopmentStrategy`. Returns `null` when `$editor`
  is unset or the file does not exist (except `action: 'create'`).
- **"Dumped from" locations can skip frames silently.** `findCallerLocation()`
  ignores frames whose docblock contains `@tracySkipLocation` and frames under
  `Debugger::$transparentPaths`.
- **`capture()` swallows output**: `ob_start(fn() => '')` with an output-eating
  callback; on a throw it cleans the buffer and rethrows. Most template
  rendering goes through it.
- **`isHtmlMode()` is the global "may I inject into this response" gate** —
  false on AJAX (`X-Requested-With` / `X-Tracy-Ajax`), CLI, a missing
  `HTTP_HOST`, or an already-sent non-`text/html` `Content-Type` header.
