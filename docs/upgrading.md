# Upgrading tracy/tracy

## 2.12.1

- **Changed** `Tracy\Debugger::dump()` called with `return: true` now applies `Tracy\Debugger::$keysToHide` and, in CLI, also `$maxDepth`, `$maxLength` and `$maxItems`; previously it ignored them (69f595b0)
- **Changed** `Tracy\Debugger::enable()` called again after output has already started is now a silent no-op; previously it threw `LogicException` (1ddaa887)
- **Changed** `Tracy\Dumper` now checks properties coming from `__debugInfo()` or from a custom object exporter against `Tracy\Debugger::$keysToHide` and the scrubber; previously such properties were always shown unmasked (e726c8f0)
- **Changed** `Tracy\Logger::$emailSnooze` now throws `InvalidArgumentException` when its value cannot be parsed by `strtotime()`; previously throttling was silently disabled and an email was sent for every error (ed5e3a48)
- **Changed** `Tracy\Bridges\Psr\TracyToPsrLoggerAdapter::log()` now logs an exception passed in the PSR-3 context only once when the message is empty or equal to the exception message; previously the message was logged a second time (6ccd6e55)

## 2.12.0

- **Deprecated** `Tracy\BlueScreen::$collapsePaths`: use `Tracy\Debugger::$transparentPaths`, a static property filled when the class is loaded; the old name is silently deprecated and its value is still merged in (0deea90e)
- **Changed** `Tracy\BlueScreen::renderToFile()` now writes a Markdown companion file with the same basename next to the HTML file (1dbb02d7)
- **Changed** `Tracy\Logger::getExceptionFile()` now reuses an existing log file only when its name ends with the exception hash and `.html`; previously the hash could appear anywhere in the name (1dbb02d7)

## 2.11.1

- **Signature** `Tracy\Bridges\Psr\PsrToTracyLoggerAdapter::log()` now declares the return type `void`; affects subclasses overriding it (2f4c7df4)
- **Config** `tracy.keysToHide` now fills `Tracy\Debugger::$keysToHide` as well, not only `Tracy\BlueScreen::$keysToHide`, so the listed keys are masked in all dumps (ebd29547)

## 2.11.0

- **Renamed** the Tracy Bar panel id `Tracy:errors` → `Tracy:warnings`; the old id was removed (0dda9390)

## 2.10.5

- **Renamed** `Tracy\Debugger::VERSION` → `Tracy\Debugger::Version`; the old name is silently deprecated (3a6fb902)
- **Deprecated** `Tracy\Debugger::PRODUCTION`: use `Tracy\Debugger::Production`; the old name is silently deprecated (alias since 2.9.6) (3a6fb902)
- **Deprecated** `Tracy\Debugger::DEVELOPMENT`: use `Tracy\Debugger::Development`; the old name is silently deprecated (alias since 2.9.6) (3a6fb902)
- **Deprecated** `Tracy\Debugger::DETECT`: use `Tracy\Debugger::Detect`; the old name is silently deprecated (alias since 2.9.6) (3a6fb902)
- **Deprecated** `Tracy\Debugger::COOKIE_SECRET`: use `Tracy\Debugger::CookieSecret`; the old name is silently deprecated (alias since 2.9.6) (3a6fb902)

## 2.10.0

- **Removed** `Tracy\FireLogger`; FireLogger support was dropped without replacement (8eaf4f25)
- **Removed** `Tracy\Debugger::fireLog()`; FireLogger support was dropped without replacement (8eaf4f25)
- **Removed** `Tracy\Debugger::getFireLogger()`; FireLogger support was dropped without replacement (8eaf4f25)
- **Removed** `Tracy\Debugger::$showFireLogger`; FireLogger support was dropped without replacement (8eaf4f25)
- **Signature** `Tracy\ILogger::log()` is now `log(mixed $value, string $level = self::INFO)`; a custom implementation must declare the same types or PHP reports an incompatible signature (9cf27348)
- **Signature** native types were added to parameters, return values and properties; notably `Tracy\Debugger::enable(bool|string|array|null $mode = null, ?string $logDirectory = null, string|array|null $email = null)`, `Tracy\Debugger::$email`, `Tracy\Debugger::$logDirectory`, `Tracy\Debugger::$browser`, `Tracy\Debugger::$errorTemplate`, `Tracy\Debugger::$productionMode`, `Tracy\Debugger::$showLocation`, `Tracy\Debugger::$scream`, `Tracy\Debugger::$logSeverity`, `Tracy\Logger::__construct()` and `Tracy\BlueScreen::addFiber()`. Assigning a value of another type to a now typed static property throws `TypeError` (9cf27348)

## 2.9.8

- **Changed** `Tracy\BlueScreen::highlightLine()` no longer appends the closing `</code>` tag, which `Tracy\BlueScreen::highlightPhp()` adds instead; also in 2.10.5 (ba708e94)

## 2.9.6

- **Renamed** `Tracy\Debugger::PRODUCTION` → `Tracy\Debugger::Production`; the old name still works as an alias (b6cd4a42)
- **Renamed** `Tracy\Debugger::DEVELOPMENT` → `Tracy\Debugger::Development`; the old name still works as an alias (b6cd4a42)
- **Renamed** `Tracy\Debugger::DETECT` → `Tracy\Debugger::Detect`; the old name still works as an alias (b6cd4a42)
- **Renamed** `Tracy\Debugger::COOKIE_SECRET` → `Tracy\Debugger::CookieSecret`; the old name still works as an alias (b6cd4a42)

## 2.9.2

- **Changed** `Tracy\Dumper::dump()` with the option `Tracy\Dumper::HASH => false` now renders a repeated array or object in full; previously it was collapsed to "see above" / "see below" (d9c2e36b)

## 2.9.1

- **Changed** `Tracy\Debugger::$maxDepth` now defaults to 15; previously 7. Set it to 7 explicitly to keep the previous depth (d6d21012)

## 2.9.0

- **Changed** `Tracy\Debugger::$scream` no longer changes `error_reporting()` globally; it now only controls whether `Tracy\Debugger::errorHandler()` processes a silenced error (52a9cc0c)
- **Config** `tracy.logSeverity` accepts as list items only strings naming `E_*` constants; a raw integer or an arbitrary string is rejected, pass a single int bitmask instead of a list (74a9fbee)

## 2.8.4

- **Changed** `Tracy\Dumper::ITEMS` with the value `0` now means no limit; previously it truncated the output to zero items (0bc1b627)
- **Changed** the scrubber callback (`Tracy\Dumper::SCRUBBER`, `Tracy\BlueScreen::$scrubber`) is now called for non-string keys as well, cast to string; previously it was not called for them (c2f56c43)

## 2.8.2

- **Changed** `Tracy\Debugger::$maxDepth` now defaults to 7; previously 3 (845130ec)
- **Changed** `Tracy\BlueScreen::$collapsePaths` now defaults to the `tracy/`, `nette/` and `latte/` directories; previously to the whole `vendor` directory, so stack frames of other packages are no longer collapsed (8c7bdf03)
- **Changed** `Tracy\Debugger::dump()` with `$return = true` now returns plain text in CLI; previously the captured HTML output (34ed4467)

## 2.8.1

- **Changed** `Tracy\BlueScreen::$maxDepth` now defaults to 5; previously 3 (e23800ff)
- **Changed** `Tracy\BlueScreen::$keysToHide` is no longer matched against virtual (computed) properties, so such a property is dumped in full (47cab47b)
- **Changed** `Tracy\BlueScreen::$scrubber` is no longer called for virtual (computed) properties (47cab47b)

## 2.8.0

- **Renamed** `Tracy\Dumper::LOCATION_LINK` → `Tracy\Dumper::LOCATION_SOURCE`; the old name still works as an alias (67e9e791)
- **Signature** `Tracy\BlueScreen::highlightFile()` and `Tracy\BlueScreen::highlightPhp()` lost the `$vars` and `$keysToHide` parameters together with the feature of inlining dumped variables into the source; drop them from calls (7d2862e9)
- **Changed** `Tracy\Dumper::LOCATION_SOURCE` now implies `Tracy\Dumper::LOCATION_CLASS`, whose bit value changed from `0b0100` to `0b0001` (67e9e791)
- **Changed** `Tracy\Debugger::$showLocation` now defaults to `null` and HTML dumps show the source location by default; previously the default was `false` and the location was hidden (2deb1d2d)
- **Changed** `Tracy\Dumper::DEPTH` now defaults to 7; previously 4 (5f275f6c)
- **Changed** `Tracy\Dumper::dump()`, `Tracy\Dumper::toHtml()` and `Tracy\Dumper::toText()` now truncate arrays and objects to 100 items; pass `Tracy\Dumper::ITEMS` to change or lift the limit (1c4dc56c)
- **Changed** `Tracy\Dumper::dump()` now emits inline `<style>` tags (and `<script>` tags unless Tracy is enabled) with Tracy's assets on the first HTML dump of a request (c481ec05)
- **Changed** `Tracy\Dumper::dump()` in CLI now writes the terminal output directly to `STDOUT` and bypasses output buffering; previously it echoed it (c5855a03)
- **Changed** `Tracy\Dumper::toHtml()`, `Tracy\Dumper::toText()` and `Tracy\Dumper::toTerminal()` no longer use late static binding, so a subclass is not instantiated through them (2eb960e9)

## 2.7.9

- **Signature** `Tracy\Bridges\Psr\TracyToPsrLoggerAdapter::log()` now declares the return type `void`; affects subclasses overriding it; also in 2.8.8 (d46d6558)

## 2.7.0

- **Config** `tracy.maxLen` → `tracy.maxLength`; the old key is now rejected as unknown (it never had any effect) (1c3317b6)
- **Config** the values of the `tracy` section are now validated by `Nette\Schema`; a value of the wrong type that previously passed silently now fails the container compilation (1c3317b6)

## 2.6.4

- **Renamed** parameter `$priority` of `log()` → `$level` in `Tracy\ILogger`, `Tracy\Logger`, `Tracy\Debugger`, `Tracy\FireLogger` and `Tracy\Bridges\Psr\PsrToTracyLoggerAdapter`; the old name was removed, which affects named arguments (1cd808a9)

## 2.6.3

- **Changed** `Tracy\Bridges\Psr\PsrToTracyLoggerAdapter::log()` now forwards a `Throwable` to the wrapped PSR logger as `Class: message #code in file:line`; previously only the exception message (45349ccc)

## 2.6.1

- **Replaced** `Tracy\Dumper::LIVE` with `Tracy\Dumper::LAZY`: lazy loading through JavaScript is controlled by `Tracy\Dumper::LAZY`, while `Tracy\Dumper::LIVE` now means collecting into the shared snapshot `Tracy\Dumper::$liveSnapshot` (ebd3f509)
- **Deprecated** `Tracy\Debugger::$maxLen`: use `Tracy\Debugger::$maxLength`; the property is declared again after having been removed in 2.6.0, but it is ignored and the old name is silently deprecated (46046a46)

## 2.6.0

- **Renamed** `Tracy\Debugger::$maxLen` → `Tracy\Debugger::$maxLength`; the old name was removed (c0d9d117)
- **Signature** `Tracy\BlueScreen::renderToFile()` now returns `bool`; previously `void`; affects subclasses overriding it (3030c0a8)
- **Signature** the default of the array parameter changed from `null` to `[]` in `Tracy\Dumper::dump()`, `Tracy\Dumper::toHtml()`, `Tracy\Dumper::toText()`, `Tracy\Dumper::toTerminal()`, `Tracy\Debugger::barDump()`, `Tracy\BlueScreen::highlightFile()` and `Tracy\BlueScreen::highlightPhp()`; passing `null` explicitly now throws `TypeError` (16703617)
- **Signature** `Tracy\Helpers::editorUri()`: the parameters `$search` and `$replace` are now `string` with the default `''`; previously `null` (daa115b6)
- **Signature** `Tracy\Dumper::__construct()` is private, so the class can no longer be instantiated; use its static methods (8358cd1f)
- **Signature** native types were added to parameters and return values; notably `Tracy\Bar::addPanel()`, `Tracy\BlueScreen::addPanel()`, `Tracy\BlueScreen::render()`, `Tracy\BlueScreen::renderToFile()`, `Tracy\Debugger::enable()`, `Tracy\Debugger::log()`, `Tracy\Logger::__construct()` and `Tracy\Logger::log()`; subclasses must declare matching types (daa115b6)
- **Changed** `Tracy\Debugger::barDump()` is rendered as a lazy dump loaded by JavaScript; previously as static HTML (de7fd36a)
- **Changed** `Tracy\Dumper::toHtml()` renders collapsed parts of arrays and objects as a lazy placeholder loaded by JavaScript; pass `Tracy\Dumper::LIVE => false` for the previous static HTML (a5333872)
- **Changed** `Tracy\Dumper::toHtml()` renders keys containing punctuation without quotes; previously they were quoted (b21853c1)
