# Dumper

Dumping is **two phases** and rendering (in the default `lazy = null` mode) is
**not single-pass**.

## Describe → render

`Dumper` is a facade over a `Describer` and a `Renderer`; `asHtml`/`asTerminal` run
`describe($var)` (phase 1) then `render($model)` (phase 2).

- **`Describer`** produces a model `{value, snapshot, location}`. A scalar stays a
  **native PHP value** only when the JSON round-trip is lossless (JS-safe ints,
  finite non-integer floats, strings `encodeString` leaves unchanged); everything
  else (a binary string, `5.0`, `NAN`, structures) becomes a `Value` object — so
  the "tree" is a mix of native values and `Value`s. `maxLength`/`maxItems` apply
  only at `depth > 0`.
- **The `$key` argument of `toHtml`/`toText` shifts the value to depth 1**
  (`asHtml`/`asTerminal` describe `[$key => $var]`), so with a key the top-level
  value *is* subject to `maxLength`, `maxItems` and `keysToHide` — BlueScreen passes
  a key almost everywhere.
- **`Exposer`** extracts object properties by reflection, including
  private/protected (mangled keys) and marks dynamic properties. Property source
  precedence in `Describer::exposeObject` is: matching object exposer →
  `__debugInfo()` (only with `DEBUGINFO`) → reflection; exactly one source is used.
  Exposer/exporter dispatch is **not insertion order**: `describe()` sorts
  `objectExposers` most-derived-first and the first match wins (`''` matches
  everything). Extension points: `Dumper::$objectExporters` (objects),
  `Dumper::$resources` (resource types), `Dumper::addEnumProperty()`.
- **`Renderer`** produces HTML; text and terminal output are the same HTML with
  `lazy = false` piped through `Helpers::htmlToText`/`htmlToAnsi`, and the ANSI
  colours are keyed by the `tracy-dump-*` class names — a new HTML construct must
  survive `strip_tags` and keep that class naming.

## The snapshot: objects/refs are stored once, referenced by placeholder

Objects, resources, and referenced arrays are **not serialized inline**. Each is
put into a shared `snapshot` array keyed by `spl_object_id` / `r<id>` / `p<refId>`,
and at the point of use a `Value` of type **`TypeRef`** is emitted. The renderer
dereferences a `TypeRef` back through the snapshot. This is why an object appearing
in many places is expanded once. Two invariants hang off this:

- **`Value->holder` pins the live object** so GC cannot recycle its
  `spl_object_id` — the snapshot key. Dropping `holder` allows key collisions in a
  shared snapshot.
- **Infinite recursion is broken at describe time**: re-encountering an
  object/array at equal-or-greater depth yields a `TypeRef` instead of descending.

Three lazy modes drive how much goes to the client:

- **`lazy = false`** — pure server-side HTML, no snapshot.
- **`lazy = true`** — the whole value goes into `data-tracy-dump` + the snapshot into
  `data-tracy-snapshot`; the JS renders it. Applies to every `Value` (including
  `NAN`, binary strings, resources) and non-empty arrays; only *native* scalars and
  the empty array fall through to server-side rendering.
- **`lazy = null`** (default, "collapsed parts") — HTML is rendered, but collapsed
  nodes are serialized into their toggler's `data-tracy-dump` (objects and
  referenced arrays as refs, plain arrays inline) and **only the reachable slice**
  of the snapshot (`Renderer::copySnapshot`) is emitted, so clicking a collapsed
  node expands it from client-side data. In both lazy modes every node at depth ≥ 2
  is collapsed regardless of options; `COLLAPSE` governs depth 0 and
  `COLLAPSE_COUNT` depth 1 (in `lazy = false` mode `COLLAPSE_COUNT` applies at
  every depth ≥ 1).

**Only BlueScreen uses a shared snapshot.** `BlueScreen::getDumper()` passes
`SNAPSHOT => &$this->snapshot`, which puts the Dumper into *collecting mode*
(derived in the constructor from `SNAPSHOT`/`LIVE`; it forces `lazy = true` and
makes `copySnapshot` a no-op — the whole snapshot is emitted, not a slice). It is
filled by reference while `content.phtml` renders each dump and serialized once, at
the very end of that template, into `<meta itemprop=tracy-snapshot>`;
`BlueScreen::renderTemplate` resets `$this->snapshot` before and after the
`require`. Bar dumps (`Debugger::barDump()`) are **self-contained** (`LAZY => true`,
own `data-tracy-snapshot` each); `Dumper::$liveSnapshot` (`LIVE`) is only a sink for
third-party panels, flushed and reset by `panels.phtml` in its own meta tag.

## Depth, hiding, and cycles

`Describer` defaults: `maxDepth = 7`, `maxLength = 150`, `maxItems = 100`; every
Debugger path overrides them (`Debugger::dumpOptions()` feeds `dump()`/`barDump()`
from `Debugger::$maxDepth` etc.; `Debugger::agentDumpOptions()` and
`BlueScreen::getAgentDumper()` use depth 3; `BlueScreen::$maxDepth` is 5 and is
independent of `Debugger::$max*`). Sensitive values (`SensitiveParameterValue`, the
`scrubber`, or a key/`Class::$key` in `keysToHide`, matched case-insensitively)
render as `***** (type)`. Hiding covers array keys, reflected properties and the
arrays returned by `__debugInfo`/object exporters — but **not** properties an
exporter adds via `addPropertyTo()` with the default `PropertyVirtual` type.

**Cycles are broken at describe time (the `TypeRef` depth guard above) but
classified at render time:** the renderer tracks `parents` (open on the current
path) and `above` (already rendered) by id, labelling a ref `RECURSION` for a true
cycle; the `see above` / `see below` labels for a non-cyclic repeat exist only in
`lazy = false` (text) output — in HTML the repeat is a collapsed ref expanded by the
client.
