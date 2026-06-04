# To My Agents!

It is my fervent wish that this file guide every AI coding agent working with code in this repository.

## Documentation

Any distilled, agent-facing documentation for this package - how it works
internally - lives in `docs/`. Consult it before non-trivial changes; it is the
source of truth from which the public manual is distilled.

Tracy is several independent mechanisms that share little context (error handling,
deferred content, the dumper, BlueScreen, the Bar, the logger). Read the relevant
`docs/internals/` seam before editing one - especially `deferred-content.md`, the
most counterintuitive part.

## Project Overview

Tracy is a debugging and error-visualization library for PHP: BlueScreen error
pages, the floating debug Bar with an extensible panel system, an advanced variable
Dumper, and a production error Logger. It auto-detects development vs production and
emits **markdown for agents** (automated browsers via `navigator.webdriver`, or any
client sending an `X-Tracy-Agent` header) instead of HTML.

- **PHP Version**: 8.2 - 8.5
- **Package**: `tracy/tracy` (currently v2.12)

## Essential Commands

```bash
# Run all tests - HTML tests only run under php-cgi, so pass -p php-cgi
vendor/bin/tester tests -p php-cgi -s
vendor/bin/tester tests/Dumper/ -s

# Static analysis (PHPStan level 8)
composer phpstan

# JavaScript assets
npm run lint         # and lint:fix

# Rebuild templates: .latte/*.agent.latte assets -> .phtml in dist/
# (one-time setup: cd tools/latte-convert && composer install)
composer compile-templates
```

## Conventions

- Every PHP file starts with `declare(strict_types=1);`; **tabs**; return type and
  opening brace on separate lines; Nette Coding Standard (`ncs.php`). JS is linted
  with `@nette/eslint-plugin`.
- Tests are Nette Tester `.phpt` using `test()` and `getTempDir()`. **CI runs both
  `php` and `php-cgi`**; UI-rendering tests need `php-cgi`.
- Templates are `.latte` (HTML-escaping) / `*.agent.latte` (text/markdown, no
  escaping) compiled to committed `.phtml` in `dist/` via
  `composer compile-templates` - edit the source, rebuild.

## Working in this repo

Where the obvious reading of the code is wrong; each line has its seam in
`docs/internals/`, read it before touching the code:

- **Error handling** (`error-handling.md`): `enable()` never starts an output
  buffer, it only records `$obLevel`; handlers register once (shutdown ->
  exception -> error) but the strategy is resolved lazily on every call, and
  `dispatch()` runs before registration.
- **Deferred content** (`deferred-content.md`): AJAX and post-redirect Bars travel
  through the session, and the coarse `FileSession` lock is *load-bearing* for
  that, not just a trap; the ordinary Bar is emitted inline and needs no session.
- **Dumper** (`dumper.md`): describe -> render over a snapshot; cycles are cut at
  describe time and labelled at render time. Only BlueScreen shares one snapshot
  per render; Bar dumps are self-contained.
- **BlueScreen** (`bluescreen.md`): panels are called once per exception in the
  chain plus once with `null`; nothing may trigger autoloading while rendering.
- **Logger** (`logger.md`): dedup by an `xxh128` hash of the exception chain (an
  existing report is never overwritten); email is throttled through the
  `email-sent` marker.
- **PHP <-> JS** (`js-contract.md`): the boundary is coupled purely by strings with
  no static checking; CSS isolation is a `<tracy-div>` host plus an aggressive
  `reset.css`, no Shadow DOM.
- **Two global gates** (`helpers.md`): `Helpers::isHtmlMode()` decides whether
  Tracy may inject into the response, `Helpers::isAgent()` (cookie or
  `X-Tracy-Agent` header) switches the output to markdown.
- User-facing how-to (configuration, custom panels/loggers/scrubbers, CSP, editor
  integration, session/nginx recipes) is manual material and lives in the web docs.
