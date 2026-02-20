# Dumper

Dumping is **two phases** and rendering (in the default `lazy = null` mode) is
**not single-pass**.

## Describe → render

`Dumper` is a facade over a `Describer` and a `Renderer`; `asHtml`/`asTerminal` run
`describe($var)` (phase 1) then `render($model)` (phase 2).

- **`Describer`** produces a model `{value, snapshot, location}`. A scalar stays a
  **native PHP value** only when the JSON round-trip is lossless — ints within the
  JS-safe range, finite non-integer-valued floats, strings that `encodeString`
  leaves unchanged; everything else (a short binary string, `5.0`, `NAN`, structures)
  becomes a `Value` object — so the "tree" is a mix of native values and `Value`s.
  `maxLength` truncation applies only at `depth > 0`; a top-level string is never
  truncated.
- **`Exposer`** extracts object properties by reflection, including private/protected
  (via mangled keys `"\x00Class\x00name"` / `"\x00*\x00name"`) and marks dynamic
  properties. Exposer/exporter dispatch is **not insertion order**: `describe()`
  `uksort`s `objectExposers` most-derived-first and the first match wins (`''`
  matches everything).
- **`Renderer`** `match`-dispatches on `Value::Type*`.

## The snapshot: objects/refs are stored once, referenced by placeholder

Objects, resources, and referenced arrays are **not serialized inline**. Each is
put into a shared `snapshot` array keyed by `spl_object_id` / `r<id>` / `p<refId>`,
and at the point of use a `Value` of type **`TypeRef`** is emitted. The renderer
dereferences a `TypeRef` back through the snapshot. This is why an object appearing
in many places is expanded once. Two invariants hang off this:

- **`Value->holder` pins the live object** so GC cannot recycle its
  `spl_object_id` — the snapshot key. Dropping `holder` allows key collisions in a
  shared/live snapshot.
- **Infinite recursion is broken at describe time**: re-encountering an
  object/array at equal-or-greater depth yields a `TypeRef` instead of descending.

Three lazy modes drive how much goes to the client:

- **`lazy = false`** — pure server-side HTML, no snapshot.
- **`lazy = true`** — the whole value goes into `data-tracy-dump` + the snapshot into
  `data-tracy-snapshot`; the JS renders it. Only for non-empty arrays and objects —
  a scalar falls through to the collapsed-parts branch and renders server-side.
- **`lazy = null`** (default, "collapsed parts") — HTML is rendered, but collapsed
  nodes are serialized as refs and **only the reachable slice** of the snapshot
  (`copySnapshot` → `snapshotSelection`) is emitted, so clicking a collapsed node
  expands it from client-side data.

For the Bar and BlueScreen the snapshot is **shared/live** across all dumps on the
page (`Dumper::$liveSnapshot` or a passed `SNAPSHOT` array + `collectingMode`) and
is written **once** for the whole page, by the templates themselves: they read
`$liveSnapshot[0]` / `BlueScreen::$snapshot[0]` directly into a
`<meta itemprop=tracy-snapshot>` tag and then reset it. (The public
`formatSnapshotAttribute()` helper is for third-party integrations — nothing in
`src` calls it.)
In collecting mode `copySnapshot` is a **no-op** — the reachable-slice mechanism
applies only to standalone dumps; the live snapshot is emitted whole.

## Depth, hiding, and cycles

Defaults: `maxDepth = 7`, `maxLength = 150`, `maxItems = 100`. Sensitive values
(`SensitiveParameterValue`, the `scrubber`, or a key/`Class::$key` in `keysToHide`)
render as `***** (type)`. **Cycles are broken at describe time (the `TypeRef`
depth guard above) but classified at render time:** the renderer tracks `parents`
(open on the current path) and `above` (already rendered) by id, labelling a ref
`RECURSION` for a true cycle and `see above` / `see below` for a non-cyclic repeat.

(There is no `Dumper::addExporter()` — object exporters are added to the static
`$objectExporters` / the `OBJECT_EXPORTERS` option.)
