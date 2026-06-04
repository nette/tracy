# Logger

`Logger::log()` appends a text line to `<level>.log` and, for a `Throwable` at
**any** level, writes an HTML BlueScreen report (plus a `.md` companion) — but only
once per distinct exception; then, for `ERROR`/`EXCEPTION`/`CRITICAL`, it sends the
throttled email. The order matters: the log line is written first (carrying the
report's basename), so an exception from the mailer or an invalid `emailSnooze`
propagates out of `log()` only after the line and the report exist.

## Hash-based deduplication

`getExceptionFile()` hashes the whole exception chain — `[class, message, code,
file, line, trace-without-args]` — with **`xxh128`, truncated to 10 chars**. Because
the **arguments are stripped from the trace**, the same exception thrown with
different argument values produces the **same hash**. It then scans the directory
for an existing `…<hash>.html` (regardless of level prefix — the first-logged level
names the file); if one exists it is **returned and not rewritten**, so a recurring
error only appends a line to `<level>.log` while the HTML/MD report is generated
once. The report filename is `<level>--<Y-m-d--H-i>--<hash>.html`, `renderToFile`
opens it with `fopen(…, 'x')` (never overwrites), and the `.md` companion is
produced only alongside a freshly created `.html`.

## Email snooze

`sendEmail()` → `throttle()` rate-limits through a marker file `email-sent` under an
exclusive `flock`: the mail is sent when the marker is empty or its mtime plus the
`emailSnooze` interval (`Helpers::parseInterval` — seconds or a `strtotime` string;
an unparsable value **throws**) is in the past, and **the marker is refreshed only
after the mailer returns** — a throwing mailer leaves the window open and the
exception propagates. An unopenable marker file silently means "no email".
`Debugger::getLogger()` binds the logger's `directory`/`email` by reference to
`Debugger::$logDirectory`/`$email` and hands it `Debugger::getBlueScreen()`, so
BlueScreen panels and `keysToHide` apply to logged reports as well.
