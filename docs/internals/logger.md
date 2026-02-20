# Logger

`Logger::log()` appends a text line to `<level>.log`
(`file_put_contents(…, FILE_APPEND | LOCK_EX)`) and, for a `Throwable`, writes an
HTML BlueScreen report (plus a `.md` companion) — but only once per distinct
exception.

## Hash-based deduplication

`getExceptionFile()` hashes the whole exception chain — `[class, message, code,
file, line, trace-without-args]` — with **`xxh128`, truncated to 10 chars**. Because
the **arguments are stripped from the trace**, the same exception thrown with
different argument values produces the **same hash**. It then scans the directory
for an existing `…<hash>.html`; if one exists it is **returned and not rewritten**,
so a recurring error only appends a line to `<level>.log` while the HTML/MD report is
generated once. The report filename is `<level>--<Y-m-d--H-i>--<hash>.html` and
`renderToFile` opens it with `fopen(…, 'x')` (never overwrites).

## Email snooze

Emails are sent only for `ERROR`/`EXCEPTION`/`CRITICAL`, and rate-limited by a marker
file `email-sent`: the send condition is `filemtime('email-sent') + $snooze <
time()` **and** an atomic `file_put_contents('email-sent', 'sent')` in the same
expression — so a successful send both fires the mail and resets the snooze window
(`emailSnooze` default `'2 days'`, parsed via `strtotime`). The default mailer is
PHP `mail()` with a UTF-8 message and an `X-Mailer: Tracy` header.
