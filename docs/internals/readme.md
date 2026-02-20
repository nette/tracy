# Tracy internals

How Tracy works underneath, for agents editing it. Several independent mechanisms
that share little context, so split by seam:

- **[error-handling.md](error-handling.md)** — `Debugger::enable()`, handler
  registration order, the Development/Production strategies, and fatal-error
  capture.
- **[deferred-content.md](deferred-content.md)** — the counterintuitive mechanism
  by which the Bar/BlueScreen survive a redirect or ride an AJAX response via the
  session, plus `FileSession` locking.
- **[dumper.md](dumper.md)** — the two-phase describe→render pipeline and the
  snapshot mechanism (rendering is not single-pass).
- **[bluescreen.md](bluescreen.md)** — the error page, repeated panel invocation,
  code highlighting.
- **[bar.md](bar.md)** — the debug toolbar panel system and its deferred render.
- **[logger.md](logger.md)** — file logging, hash-based dedup, email snooze.
- **[js-contract.md](js-contract.md)** — the string-coupled PHP↔JS boundary:
  entry points, the requestId round-trip, dump attributes, CSP nonce.
- **[helpers.md](helpers.md)** — cross-cutting `Helpers` gotchas (exception
  mutation, editor mapping, output capture).
