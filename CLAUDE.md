# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Tracy is a debugging and error visualization library for PHP (8.2-8.5). It provides beautiful error pages (BlueScreen), an interactive debug toolbar (Bar), advanced variable dumping, and production-ready error logging.

**Key features:**
- BlueScreen: Beautiful error/exception visualization with stack traces
- Tracy Bar: Floating debug toolbar with extensible panel system
- Dumper: Advanced variable dumping with multiple output formats
- Logger: Error logging with email notifications
- Production/development mode auto-detection
- AI agent support: clients sending `Accept` without `text/html` (e.g. curl, AI agents) get text/markdown output

## Essential Commands

### Testing
```bash
# Run all tests - ALWAYS use php-cgi (html tests only run with php-cgi)
vendor/bin/tester tests -p php-cgi -s

# Run single test file
vendor/bin/tester tests/Tracy/Debugger.timer().phpt -p php-cgi -s

# Run tests in specific directory
vendor/bin/tester tests/Dumper/ -s
```

### Code Quality
```bash
# Run PHPStan static analysis (level 7)
composer run phpstan

# Lint JavaScript assets
npm run lint
npm run lint:fix
```

## Core Architecture

### Main Components (Facade Pattern)

**Tracy\Debugger** (src/Tracy/Debugger/Debugger.php) - Central facade
- `enable()` - Initialize Tracy
- `dump()` / `bdump()` - Variable dumping
- `log()` - Error logging
- `timer()` - Performance profiling
- Global functions available: `dump()`, `dumpe()`, `bdump()`

**Strategy Pattern:**
- `DevelopmentStrategy` - Shows full debug info, Tracy Bar
- `ProductionStrategy` - Logs errors, shows user-friendly messages
- `DeferredContent` - Manages deferred rendering for AJAX/redirect requests
- Auto-detection: localhost = development, otherwise production
- Agent detection: `Helpers::isAgent()` checks `Accept` header — true when present and doesn't contain `text/html` (matches curl `*/*`, `text/plain`, `text/markdown`, etc.)

### Core Components

**BlueScreen** (src/Tracy/BlueScreen/)
- Error/exception page rendering with stack traces
- Multiple output formats: HTML, CLI, text/markdown (for AI agents)
- `renderAsText()` outputs markdown template
- Extensible via `addPanel()` callbacks
- Additional: `CodeHighlighter.php` for syntax highlighting
- CSS isolation via Shadow DOM (`<tracy-bs>` custom element, created in `bluescreen.js`)

**Bar** (src/Tracy/Bar/)
- Debug toolbar with panel system
- Interface: `IBarPanel` for custom panels
- Built-in panels: dumps, info, warnings
- AJAX request tracking via session storage
- `renderAsText()` outputs HTML comment with timing/memory info template
- CSS isolation via Shadow DOM (shadow host created in `bar.js` `Debug.init()`)

**Dumper** (src/Tracy/Dumper/)
- Component architecture:
  - `Describer` - Analyzes variable structure
  - `Exposer` - Extracts object properties (including private/protected)
  - `Renderer` - Formats output (HTML/CLI/Text)
  - `Value` - Represents dumped values
- Supports lazy loading, themes (light/dark)
- Standalone dumps wrapped in `<tracy-dump>` custom element with Shadow DOM

**Logger** (src/Tracy/Logger/)
- File-based error logging
- Email notifications via `ILogger` interface
- Severity filtering
- PSR-3 compatible via adapters in `Bridges/Psr/`

**Session Storage** (src/Tracy/Session/)
- `FileSession` - Custom file-based storage (default)
- `NativeSession` - PHP session integration
- Used for AJAX/redirect request tracking

### Directory Structure

```
src/
├── Bridges/              # Framework integrations
│   ├── Nette/           # Nette DI, Mail integration
│   └── Psr/             # PSR-3 logger adapters
└── Tracy/               # Core library
    ├── Bar/             # Debug toolbar
    │   ├── assets/      # Latte templates, JS, CSS
    │   ├── panels/      # Built-in panel templates (.latte)
    │   └── dist/        # Compiled .phtml templates
    ├── BlueScreen/      # Error visualization
    │   ├── assets/      # Latte templates, JS, CSS
    │   └── dist/        # Compiled .phtml templates
    ├── Debugger/        # Main facade & strategies
    │   ├── assets/      # Error template (.latte)
    │   └── dist/        # Compiled .phtml template
    ├── Dumper/          # Variable dumping engine
    │   └── assets/      # JS, CSS (light/dark themes)
    ├── Logger/          # Error logging
    ├── Session/         # Session storage
    ├── OutputDebugger/  # Output buffer debugging
    └── assets/          # Shared JS/CSS utilities

tests/                   # 121 .phpt test files (65 Tracy + 52 Dumper + 4 Bridges)
examples/               # Live examples and demos
tools/                  # Utilities (phar creation, editor integration)
```

## Testing Conventions

**Test Framework:** Nette Tester (not PHPUnit)

**Test file structure:**
```php
<?php
declare(strict_types=1);

use Tester\Assert;
use Tracy\Debugger;

require __DIR__ . '/../bootstrap.php';

test('descriptive test name', function () {
	// Test code
	Assert::same('expected', $actual);
});

test('another test case', function () {
	// More test code
});
```

**Important:**
- Test files use `.phpt` extension
- Use `test(string $title, Closure $function)` helper
- No comments before `test()` calls - description is the first parameter
- Bootstrap: `tests/bootstrap.php`
- Temp directory: `getTempDir()` helper

**Testing exceptions:**
```php
Assert::exception(
	fn() => $object->method(),
	ExpectedException::class,
	'Expected message with %a% placeholders',
);
```

**CI Testing:**
- Tests run on PHP 8.2, 8.3, 8.4, 8.5
- Both `php` and `php-cgi` SAPI
- Ubuntu and Windows

## Code Style

**Nette Coding Standard** (based on PSR-12):
- `declare(strict_types=1)` in every PHP file
- Tab indentation
- Return type and opening brace on separate lines:
```php
public function example(
	string $param,
	array $options,
): ReturnType
{
	// method body
}
```
- Configuration: `ncs.php`
- No space before parentheses in arrow functions: `fn($a) => $b`

**JavaScript:**
- ESLint with `@nette/eslint-plugin`
- Consistent with Nette JavaScript patterns

## Configuration

### Logger Configuration
```php
$logger = Debugger::getLogger();

// Email notifications
$logger->email = 'dev@example.com';      // (string|string[]) email(s) for error notifications
$logger->fromEmail = 'me@example.com';   // (string) sender email
$logger->mailer = /* callable */;        // custom email sender, defaults to mail()
$logger->emailSnooze = '2 days';         // minimum interval for sending emails

// Log severity - which error levels are logged with HTML report
Debugger::$logSeverity = E_WARNING | E_NOTICE;
```

### Dumper Configuration
```php
Debugger::$maxLength = 150;              // maximum string length in dumps
Debugger::$maxDepth = 15;                // maximum nesting depth
Debugger::$keysToHide = ['password', 'secret', 'token'];  // hide sensitive keys
Debugger::$dumpTheme = 'dark';           // 'light' or 'dark'
Debugger::$showLocation = true;          // show dump() call location
```

### Other Configuration
```php
Debugger::$strictMode = true;            // display notices/warnings as BlueScreen
Debugger::$scream = true;                // display silenced (@) errors
Debugger::$editor = 'editor://open/?file=%file&line=%line';  // editor link format
Debugger::$errorTemplate = 'path/to/500.phtml';  // custom error 500 page
Debugger::$showBar = true;               // show Tracy Bar

// Editor path mapping (e.g., for Docker/remote servers)
Debugger::$editorMapping = [
	'/var/www/html' => '/local/project/path',
	'/home/web' => '/Users/dev/projects',
];
```

### Nette Framework Configuration

In `config/common.neon`:
```neon
tracy:
	# Logging
	email: dev@example.com
	fromEmail: robot@example.com
	emailSnooze: 2 days
	logSeverity: [E_WARNING, E_NOTICE]

	# Dumper
	maxLength: 150
	maxDepth: 15
	keysToHide: [password, pass, secret]
	dumpTheme: dark
	showLocation: true

	# Other
	strictMode: true
	scream: false
	editor: 'editor://open/?file=%file&line=%line'
	showBar: true

	# Custom panels
	bar:
		- MyPanel(@MyService)
		- Nette\Bridges\DITracy\ContainerPanel

	# BlueScreen extensions
	blueScreen:
		- DoctrinePanel::renderException

	editorMapping:
		/var/www/html: /local/path
```

**DI Services available:**
- `tracy.logger` (Tracy\ILogger)
- `tracy.blueScreen` (Tracy\BlueScreen)
- `tracy.bar` (Tracy\Bar)

## Extension Points

### Custom Tracy Bar Panels

Implement `Tracy\IBarPanel` interface:
```php
class MyPanel implements Tracy\IBarPanel
{
	public function getTab(): string
	{
		// Tab HTML (small label on Bar)
		return <<<HTML
			<span title="Explanatory tooltip">
				<svg>...</svg>
				<span class="tracy-label">My Panel</span>
			</span>
		HTML;
	}

	public function getPanel(): string
	{
		// Panel HTML (popup content)
		return <<<HTML
			<h1>My Panel Title</h1>
			<div class="tracy-inner">
			<div class="tracy-inner-container">
				<table>
					<tr><td>Info</td><td>Value</td></tr>
				</table>
			</div>
			</div>
		HTML;
	}
}

// Register
Tracy\Debugger::getBar()->addPanel(new MyPanel);
```

**Panel styling:**
- Use classes, not IDs: `tracy-addons-<ClassName>[-optional]`
- Prefix selectors: `#tracy-debug .your-class`
- Elements `<a>`, `<table>`, `<pre>`, `<code>` have predefined styles
- Toggle elements: use `tracy-toggle` class with matching `href` and `id`
- Panels live inside Shadow DOM — host page CSS cannot affect them

### BlueScreen Extensions

Add custom sections to error pages:
```php
Tracy\Debugger::getBlueScreen()->addPanel(function (?Throwable $e) {
	// Called twice: first with exception, then with null
	// First call renders at top, second call below call stack
	return [
		'tab' => 'Database Queries',
		'panel' => '<h2>Queries</h2><pre>' . implode("\n", $queries) . '</pre>',
		'bottom' => true,  // render at very bottom
	];
});
```

### Custom Loggers

Implement `Tracy\ILogger` interface:
```php
class SlackLogger implements Tracy\ILogger
{
	public function log($value, $priority = self::INFO)
	{
		// Send to Slack, Sentry, etc.
	}
}

Tracy\Debugger::setLogger(new SlackLogger);
```

**Monolog integration:**
```php
$monolog = new Monolog\Logger('main-channel');
$monolog->pushHandler(new Monolog\Handler\StreamHandler($logFilePath));

$tracyLogger = new Tracy\Bridges\Psr\PsrToTracyLoggerAdapter($monolog);
Debugger::setLogger($tracyLogger);
```

### Custom Scrubber (Hide Sensitive Data)

```php
// Prevent dumping password values
$scrubber = function(string $key, $value, ?string $class): bool {
	return preg_match('#password|secret|token#i', $key) && $value !== null;
};

Tracy\Debugger::getBlueScreen()->scrubber = $scrubber;
```

### Custom Dump Formatting

Add object exporters via `Dumper::addExporter()`

## Integration Patterns

**Basic usage:**
```php
Tracy\Debugger::enable();  // Auto-detect mode
Tracy\Debugger::enable(Tracy\Debugger::Development);  // Force mode
Tracy\Debugger::enable('secret@123.45.67.89');  // IP + cookie
```

**Nette Framework:**
- `TracyExtension` provides DI integration
- Automatic configuration via NEON

**PSR-3 Logging:**
- `PsrToTracyLoggerAdapter` - Use PSR-3 logger with Tracy
- `TracyToPsrLoggerAdapter` - Use Tracy as PSR-3 logger

## Shadow DOM Architecture

All Tracy UI components use Shadow DOM for CSS isolation from the host page:

**How it works:**
- CSS is injected into `document.head` as `<style class="tracy-debug">` (by `DeferredContent`)
- Each component creates a shadow host, attaches a shadow root, and clones CSS into it
- Host page CSS selectors cannot penetrate shadow boundaries
- Inherited properties (font, color) are set by component CSS at their root elements

**Shadow hosts:**
- **Bar**: `<tracy-bar>` host → shadow root → `<tracy-div id="tracy-debug">` (created in `bar.js` `Debug.init()`)
- **BlueScreen**: `<tracy-bs>` host → shadow root → `<tracy-div id="tracy-bs">` (created in `bluescreen.js` `BlueScreen.init()`)
- **Standalone dumps**: `<tracy-dump>` custom element with `connectedCallback()` (defined in `dumper.js`)

**JS event handling across shadow boundaries:**
- `e.composedPath()[0] || e.target` instead of `e.target` (retargeting)
- `el.getRootNode()` instead of `document` for queries (returns `ShadowRoot` or `Document`)
- `composed: true` on custom events (`tracy-beforetoggle`, `tracy-toggle`)
- `CSS.escape()` for `querySelector` with IDs containing `:` (panel IDs)

**CSS conventions:**
- `:host { display: contents; }` on shadow hosts (no layout box)
- `:root, :host` for CSS custom properties in `reset.css`
- `reset.css` contains minimal UA reset (margin/padding/box-sizing), not host-page protection
- Component CSS files (bar.css, bluescreen.css) set font/color at root level

## Asset Management

Templates use Latte-like syntax (`.latte` files) in `assets/` and `panels/` directories. Compiled `.phtml` files are in `dist/` subdirectories:
- Bar: `src/Tracy/Bar/assets/` + `panels/` → `dist/`
- BlueScreen: `src/Tracy/BlueScreen/assets/` → `dist/`
- Debugger: `src/Tracy/Debugger/assets/` → `dist/`
- Dumper: `src/Tracy/Dumper/assets/` (JS/CSS only, no templates)
- Shared JS/CSS utilities: `src/Tracy/assets/`

**Template conventions:**
- `.latte` files: HTML templates with Latte-like syntax (HTML escaping)
- `.md.latte` files: Text/markdown templates (no HTML escaping, precise whitespace control)
- Run `php convert-latte.php` to compile all templates to `.phtml` in `dist/`

## Important Notes

**Current version:** 2.11.2 (branch: v2.11)

**Development directories:**
- `exam/` - Experimental/development files
- `x/` - Scratch work (git-ignored)

**Mode detection:**
- Development: localhost (127.0.0.1 or ::1) without proxy
- Production: all other environments
- Override with `Debugger::enable($mode)` or IP addresses

**Error handling:**
- Tracy changes error reporting to E_ALL on enable
- Use `Debugger::$strictMode` to display notices as errors
- Production mode logs errors instead of displaying them

## Practical Recipes

### AJAX Request Debugging

Tracy automatically captures AJAX requests made with jQuery or native `fetch` API. They appear as additional rows in Tracy Bar.

**Disable automatic capture:**
```js
window.TracyAutoRefresh = false;
```

**Manual AJAX monitoring:**
```js
fetch(url, {
	headers: {
		'X-Requested-With': 'XMLHttpRequest',
		'X-Tracy-Ajax': Tracy.getAjaxHeader(),
	}
})
```

### Content Security Policy (CSP)

Tracy requires CSP adjustments to work properly:

**Nette Framework:**
```neon
http:
	csp:
		script-src: [nonce, strict-dynamic]
```

**Pure PHP:**
```php
$nonce = base64_encode(random_bytes(20));
header("Content-Security-Policy: script-src 'nonce-$nonce' 'strict-dynamic';");
```

**Note:** `style-src` doesn't support nonce; use `'unsafe-inline'` (avoid in production)

### Performance Optimization

If slow scripts delay Tracy loading, render the loader early:

```html
<!DOCTYPE html>
<html>
<head>
	<title>Page Title</title>
	<?php Tracy\Debugger::renderLoader() ?>
	<link rel="stylesheet" href="assets/style.css">
	<script src="https://code.jquery.com/jquery.min.js"></script>
</head>
```

### Session Storage

**Use native PHP session:**
```php
session_start();
Debugger::setSessionStorage(new Tracy\NativeSession);
Debugger::enable();
```

**Complex session initialization:**
```php
Debugger::setSessionStorage(new Tracy\NativeSession);
Debugger::enable();

// Custom session initialization
session_start();

Debugger::dispatch();  // Inform Tracy session is ready
```

### nginx Configuration

If Tracy doesn't work on nginx, fix the `try_files` directive:

```nginx
# Wrong
try_files $uri $uri/ /index.php;

# Correct
try_files $uri $uri/ /index.php$is_args$args;
```

### IDE Integration

Tracy can open files directly in your editor when clicking file names in error pages.

**Editor integration scripts:**
- Windows: `tools/open-in-editor/windows/`
- Linux: `tools/open-in-editor/linux/`
- macOS: Use built-in URL schemes

**Built-in editor URLs (macOS):**
```php
// PhpStorm
Tracy\Debugger::$editor = 'phpstorm://open?file=%file&line=%line';

// VS Code
Tracy\Debugger::$editor = 'vscode://file/%file:%line';

// TextMate
Tracy\Debugger::$editor = 'txmt://open/?url=file://%file&line=%line';
```

**Editor path mapping for remote/Docker:**
```php
Debugger::$editorMapping = [
	'/var/www/html' => 'W:\\Projects\\myapp',  // Docker to Windows
	'/app' => '/Users/dev/projects/myapp',     // Container to macOS
];
```
