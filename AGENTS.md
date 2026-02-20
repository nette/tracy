# To My Agents!

It is my fervent wish that this file guide every AI coding agent working with code in this repository.

## Documentation

Any distilled, agent-facing documentation for this package - how it works
internally and the rationale behind key design decisions - lives in `docs/`.
Consult it before non-trivial changes; it is the source of truth from which the
public manual is distilled.

Tracy is several independent mechanisms that share little context (error handling,
deferred content, the dumper, BlueScreen, the Bar, the logger). Read the relevant
`docs/internals/` seam before editing one - especially `deferred-content.md`, the
most counterintuitive part.

## Project Overview

Tracy is a debugging and error-visualization library for PHP: BlueScreen error
pages, the floating debug Bar with an extensible panel system, an advanced variable
Dumper, and a production error Logger. It auto-detects development vs production and
emits **markdown to the JS console for automated browsers** (`navigator.webdriver`).

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

- **`enable()` does NOT start an output buffer.** It records `$obLevel` and strips
  buffers *above* it (`removeOutputBuffers`). Handler registration order is
  shutdown -> exception -> error; strategy/dispatch run before registration;
  `$reserved` is the double-render guard. See `docs/internals/error-handling.md`.
- **`DeferredContent` is the counterintuitive core.** The Bar/BlueScreen survive a
  redirect and ride AJAX responses **through the session**: content is written by
  reference, then the browser fetches `?_tracy_bar=content.<id>` and consumes it
  **once**. `FileSession` holds `LOCK_EX` for the whole request and writes only in
  `__destruct` (a crash loses it).
- **The Dumper is two-phase: describe -> render, over a snapshot.** Cycles are
  broken at describe time (`TypeRef` depth guard) and labelled at render time.
  Bar/BlueScreen share one live snapshot per page.
- **BlueScreen panels are called repeatedly** (once per exception in the chain,
  plus once with `null`).
- **The Logger dedups by an `xxh128` hash** (same exception -> same file, no
  overwrite) and throttles email via an email-sent mtime (`emailSnooze`).
- **CSS isolation uses a `<tracy-div>` host element plus an aggressive
  `reset.css`** (no Shadow DOM) - the Bar and BlueScreen (`<tracy-div id="tracy-bs">`)
  both live inside `<tracy-div>` wrappers in the regular DOM.
- **The PHP <-> JS boundary is coupled purely by strings** (function names,
  element ids, attribute and storage keys) with no static checking - before
  renaming anything on either side, see `docs/internals/js-contract.md`.
- Agent detection is `Helpers::isAgent()` reading the `tracy-webdriver` cookie set by
  `bar.js`; that path feeds the console-markdown output.
- User-facing how-to (configuration, custom panels/loggers/scrubbers, CSP, editor
  integration, session/nginx recipes) is manual material and lives in the web docs.
