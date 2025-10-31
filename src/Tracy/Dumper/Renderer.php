<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tracy\Dumper;

use Tracy\Dumper\Nodes\ArrayNode;
use Tracy\Dumper\Nodes\CollectionNode;
use Tracy\Dumper\Nodes\NumberNode;
use Tracy\Dumper\Nodes\ObjectNode;
use Tracy\Dumper\Nodes\ReferenceNode;
use Tracy\Dumper\Nodes\ResourceNode;
use Tracy\Dumper\Nodes\StringNode;
use Tracy\Dumper\Nodes\TextNode;
use Tracy\Helpers;
use function count, htmlspecialchars, ini_set, is_array, is_bool, is_float, is_int, is_object, is_string, json_encode, str_repeat, str_replace, strlen, strrpos, substr, substr_count;
use const JSON_HEX_AMP, JSON_HEX_APOS, JSON_UNESCAPED_SLASHES, JSON_UNESCAPED_UNICODE;


/**
 * Visualisation of internal representation.
 * @internal
 */
final class Renderer
{
	private const TypeArrayKey = 'array';

	public int|bool $collapseTop = 14;
	public int $collapseSub = 7;
	public bool $classLocation = false;
	public bool $sourceLocation = false;

	/** lazy-loading via JavaScript? true=full, false=none, null=collapsed parts */
	public ?bool $lazy = null;
	public bool $hash = true;
	public ?string $theme = 'light';
	public bool $collectingMode = false;

	/** @var Node[] */
	private array $snapshot = [];

	/** @var Node[]|null */
	private ?array $snapshotSelection = null;
	private array $parents = [];
	private array $above = [];


	public function renderAsHtml(\stdClass $model): string
	{
		try {
			$value = $model->value;
			$this->snapshot = $model->snapshot;

			if ($this->lazy === false) { // no lazy-loading
				$html = $this->renderVar($value);
				$json = $snapshot = null;

			} elseif ($this->lazy && (is_array($value) && $value || $value instanceof Node)) { // full lazy-loading
				$html = '';
				$snapshot = $this->collectingMode ? null : $this->snapshot;
				$json = $value;

			} else { // lazy-loading of collapsed parts
				$html = $this->renderVar($value);
				$snapshot = $this->snapshotSelection;
				$json = null;
			}
		} finally {
			$this->parents = $this->snapshot = $this->above = [];
			$this->snapshotSelection = null;
		}

		$location = null;
		if ($model->location && $this->sourceLocation) {
			[$file, $line, $code] = $model->location;
			$uri = Helpers::editorUri($file, $line);
			$location = Helpers::formatHtml(
				'<a href="%" class="tracy-dump-location" title="in file % on line %%">',
				$uri ?? '#',
				$file,
				$line,
				$uri ? "\nClick to open in editor" : '',
			) . Helpers::encodeString($code, 50) . " 📍</a\n>";
		}

		return '<pre class="tracy-dump' . ($this->theme ? ' tracy-' . htmlspecialchars($this->theme) : '')
				. ($json && $this->collapseTop === true ? ' tracy-collapsed' : '') . '"'
				. ($snapshot !== null ? " data-tracy-snapshot='" . self::jsonEncode($snapshot) . "'" : '')
				. ($json ? " data-tracy-dump='" . self::jsonEncode($json) . "'" : '')
				. ($location || strlen($html) > 100 ? "\n" : '')
			. '>'
			. $location
			. $html
			. "</pre>\n";
	}


	public function renderAsText(\stdClass $model, array $colors = []): string
	{
		try {
			$this->snapshot = $model->snapshot;
			$this->lazy = false;
			$s = $this->renderVar($model->value);
		} finally {
			$this->parents = $this->snapshot = $this->above = [];
		}

		$s = $colors ? Helpers::htmlToAnsi($s, $colors) : Helpers::htmlToText($s);
		$s = str_replace('…', '...', $s);
		$s .= substr($s, -1) === "\n" ? '' : "\n";

		if ($this->sourceLocation && ([$file, $line] = $model->location)) {
			$s .= "in $file:$line\n";
		}

		return $s;
	}


	private function renderVar(mixed $value, int $depth = 0, string|int|null $keyType = null): string
	{
		return match (true) {
			$value === null => '<span class="tracy-dump-null">null</span>',
			is_bool($value) => '<span class="tracy-dump-bool">' . ($value ? 'true' : 'false') . '</span>',
			is_int($value) => '<span class="tracy-dump-number">' . $value . '</span>',
			is_float($value) => '<span class="tracy-dump-number">' . self::jsonEncode($value) . '</span>',
			is_string($value), $value instanceof StringNode => $this->renderString($value, $depth, $keyType),
			is_array($value), $value instanceof ArrayNode => $this->renderArray($value, $depth),
			$value instanceof ReferenceNode => $this->renderVar($this->snapshot[$value->targetId], $depth, $keyType),
			$value instanceof ObjectNode => $this->renderObject($value, $depth),
			$value instanceof NumberNode => '<span class="tracy-dump-number">' . Helpers::escapeHtml($value->value) . '</span>',
			$value instanceof TextNode => '<span class="tracy-dump-virtual">' . Helpers::escapeHtml($value->value) . '</span>',
			$value instanceof ResourceNode => $this->renderResource($value, $depth),
			default => throw new \Exception('Unknown type: ' . get_debug_type($value)),
		};
	}


	private function renderString(string|StringNode $str, int $depth, string|int|null $keyType): string
	{
		if ($keyType === self::TypeArrayKey) {
			$indent = '<span class="tracy-dump-indent">   ' . str_repeat('|  ', $depth - 1) . ' </span>';
			return '<span class="tracy-dump-string">'
				. "<span class='tracy-dump-lq'>'</span>"
				. (is_string($str) ? Helpers::escapeHtml($str) : str_replace("\n", "\n" . $indent, $str->content))
				. "<span>'</span>"
				. '</span>';

		} elseif ($keyType !== null) {
			$classes = [
				ObjectNode::PropertyPublic => 'tracy-dump-public',
				ObjectNode::PropertyProtected => 'tracy-dump-protected',
				ObjectNode::PropertyDynamic => 'tracy-dump-dynamic',
				ObjectNode::PropertyVirtual => 'tracy-dump-virtual',
			];
			$indent = '<span class="tracy-dump-indent">   ' . str_repeat('|  ', $depth - 1) . ' </span>';
			$title = is_string($keyType)
				? ' title="declared in ' . Helpers::escapeHtml($keyType) . '"'
				: null;
			return '<span class="'
				. ($title ? 'tracy-dump-private' : $classes[$keyType]) . '"' . $title . '>'
				. (is_string($str)
					? Helpers::escapeHtml($str)
					: "<span class='tracy-dump-lq'>'</span>" . str_replace("\n", "\n" . $indent, $str->content) . "<span>'</span>")
				. '</span>';

		} elseif (is_string($str)) {
			$len = Helpers::utf8Length($str);
			return '<span class="tracy-dump-string"'
				. ($len > 1 ? ' title="' . $len . ' characters"' : '')
				. '>'
				. "<span>'</span>"
				. Helpers::escapeHtml($str)
				. "<span>'</span>"
				. '</span>';

		} else {
			$unit = $str->binary ? 'bytes' : 'characters';
			$count = substr_count($str->content, "\n");
			if ($count) {
				$collapsed = $indent1 = $toggle = null;
				$indent = '<span class="tracy-dump-indent"> </span>';
				if ($depth) {
					$collapsed = $count >= $this->collapseSub;
					$indent1 = '<span class="tracy-dump-indent">   ' . str_repeat('|  ', $depth) . '</span>';
					$indent = '<span class="tracy-dump-indent">   ' . str_repeat('|  ', $depth) . ' </span>';
					$toggle = '<span class="tracy-toggle' . ($collapsed ? ' tracy-collapsed' : '') . '">string</span>' . "\n";
				}

				return $toggle
					. '<div class="tracy-dump-string' . ($collapsed ? ' tracy-collapsed' : '')
					. '" title="' . $str->length . ' ' . $unit . '">'
					. $indent1
					. '<span' . ($count ? ' class="tracy-dump-lq"' : '') . ">'</span>"
					. str_replace("\n", "\n" . $indent, $str->content)
					. "<span>'</span>"
					. ($depth ? "\n" : '')
					. '</div>';
			}

			return '<span class="tracy-dump-string"'
				. ($str->length > 1 ? " title=\"{$str->length} $unit\"" : '')
				. '>'
				. "<span>'</span>"
				. $str->content
				. "<span>'</span>"
				. '</span>';
		}
	}


	private function renderArray(array|ArrayNode $array, int $depth): string
	{
		$out = '<span class="tracy-dump-array">array</span> (';

		if (is_array($array)) {
			$items = $array;
			$count = count($items);
			$out .= $count . ')';
		} elseif ($array->items === null) {
			return $out . $array->length . ') …';
		} else {
			$items = $array->items;
			$count = $array->length ?? count($items);
			$out .= $count . ')';
			if ($array->id && isset($this->parents[$array->id])) {
				return $out . ' <i>RECURSION</i>';

			} elseif ($array->id && ($array->depth < $depth || isset($this->above[$array->id]))) {
				if ($this->lazy !== false) {
					$ref = new ReferenceNode($array->id);
					$this->copySnapshot($ref);
					return '<span class="tracy-toggle tracy-collapsed" data-tracy-dump=\'' . json_encode($ref) . "'>" . $out . '</span>';

				} elseif ($this->hash) {
					return $out . (isset($this->above[$array->id]) ? ' <i>see above</i>' : ' <i>see below</i>');
				}
			}
		}

		if (!$count) {
			return $out;
		}

		$collapsed = $depth
			? ($this->lazy === false || $depth === 1 ? $count >= $this->collapseSub : true)
			: (is_int($this->collapseTop) ? $count >= $this->collapseTop : $this->collapseTop);

		$span = '<span class="tracy-toggle' . ($collapsed ? ' tracy-collapsed' : '') . '"';

		if ($collapsed && $this->lazy !== false) {
			$value = $array instanceof ArrayNode && isset($array->id) ? new ReferenceNode($array->id) : $array;
			$this->copySnapshot($value);
			return $span . " data-tracy-dump='" . self::jsonEncode($value) . "'>" . $out . '</span>';
		}

		$out = $span . '>' . $out . "</span>\n" . '<div' . ($collapsed ? ' class="tracy-collapsed"' : '') . '>';
		$indent = '<span class="tracy-dump-indent">   ' . str_repeat('|  ', $depth) . '</span>';
		$this->parents[$array->id ?? ''] = $this->above[$array->id ?? ''] = true;

		foreach ($items as $item) {
			$out .= $indent
				. $this->renderVar($item->key, $depth + 1, self::TypeArrayKey)
				. ' => '
				. ($item->refId && $this->hash ? '<span class="tracy-dump-hash">&' . $item->refId . '</span> ' : '')
				. ($tmp = $this->renderVar($item->value, $depth + 1))
				. (str_ends_with($tmp, '</div>') ? '' : "\n");
		}

		if ($count > count($items)) {
			$out .= $indent . "…\n";
		}

		unset($this->parents[$array->id ?? '']);
		return $out . '</div>';
	}


	private function renderObject(ObjectNode $object, int $depth): string
	{
		$editorAttributes = '';
		if ($this->classLocation && $object->editor) {
			$editorAttributes = Helpers::formatHtml(
				' title="Declared in file % on line %%%" data-tracy-href="%"',
				$object->editor->file,
				$object->editor->line,
				$object->editor->url ? "\nCtrl-Click to open in editor" : '',
				"\nAlt-Click to expand/collapse all child nodes",
				$object->editor->url,
			);
		}

		$pos = strrpos($object->className, '\\');
		$out = '<span class="tracy-dump-object"' . $editorAttributes . '>'
			. ($pos
				? Helpers::escapeHtml(substr($object->className, 0, $pos + 1)) . '<b>' . Helpers::escapeHtml(substr($object->className, $pos + 1)) . '</b>'
				: Helpers::escapeHtml($object->className))
			. '</span>'
			. ($object->id && $this->hash ? ' <span class="tracy-dump-hash">#' . $object->id . '</span>' : '');

		if ($object->items === null) {
			return $out . ' …';

		} elseif (!$object->items) {
			return $out;

		} elseif ($object->id && isset($this->parents[$object->id])) {
			return $out . ' <i>RECURSION</i>';

		} elseif ($object->id && ($object->depth < $depth || isset($this->above[$object->id]))) {
			if ($this->lazy !== false) {
				$ref = new ReferenceNode($object->id);
				$this->copySnapshot($ref);
				return '<span class="tracy-toggle tracy-collapsed" data-tracy-dump=\'' . json_encode($ref) . "'>" . $out . '</span>';

			} elseif ($this->hash) {
				return $out . (isset($this->above[$object->id]) ? ' <i>see above</i>' : ' <i>see below</i>');
			}
		}

		$collapsed = $object->collapsed ?? ($depth
			? ($this->lazy === false || $depth === 1 ? count($object->items) >= $this->collapseSub : true)
			: (is_int($this->collapseTop) ? count($object->items) >= $this->collapseTop : $this->collapseTop));

		$span = '<span class="tracy-toggle' . ($collapsed ? ' tracy-collapsed' : '') . '"';

		if ($collapsed && $this->lazy !== false) {
			$value = $object->id ? new ReferenceNode($object->id) : $object;
			$this->copySnapshot($value);
			return $span . " data-tracy-dump='" . self::jsonEncode($value) . "'>" . $out . '</span>';
		}

		$out = $span . '>' . $out . "</span>\n" . '<div' . ($collapsed ? ' class="tracy-collapsed"' : '') . '>';
		$indent = '<span class="tracy-dump-indent">   ' . str_repeat('|  ', $depth) . '</span>';
		$this->parents[$object->id ?? ''] = $this->above[$object->id ?? ''] = true;

		foreach ($object->items as $item) {
			$out .= $indent
				. $this->renderVar($item->key, $depth + 1, $item->type ?? ObjectNode::PropertyVirtual)
				. ': '
				. ($item->refId && $this->hash ? '<span class="tracy-dump-hash">&' . $item->refId . '</span> ' : '')
				. ($tmp = $this->renderVar($item->value, $depth + 1))
				. (str_ends_with($tmp, '</div>') ? '' : "\n");
		}

		if ($object->length > count($object->items)) {
			$out .= $indent . "…\n";
		}

		unset($this->parents[$object->id ?? '']);
		return $out . '</div>';
	}


	private function renderResource(ResourceNode $resource, int $depth): string
	{
		$out = '<span class="tracy-dump-resource">' . Helpers::escapeHtml($resource->description) . '</span> '
			. ($this->hash ? '<span class="tracy-dump-hash">@' . substr($resource->id, 1) . '</span>' : '');

		if (!$resource->items) {
			return $out;

		} elseif (isset($this->above[$resource->id])) {
			if ($this->lazy !== false) {
				$ref = new ReferenceNode($resource->id);
				$this->copySnapshot($ref);
				return '<span class="tracy-toggle tracy-collapsed" data-tracy-dump=\'' . json_encode($ref) . "'>" . $out . '</span>';
			}

			return $out . ' <i>see above</i>';

		} else {
			$this->above[$resource->id] = true;
			$out = "<span class=\"tracy-toggle tracy-collapsed\">$out</span>\n<div class=\"tracy-collapsed\">";
			foreach ($resource->items as $item) {
				$out .= '<span class="tracy-dump-indent">   ' . str_repeat('|  ', $depth) . '</span>'
					. $this->renderVar($item->key, $depth + 1, ObjectNode::PropertyVirtual)
					. ': '
					. ($tmp = $this->renderVar($item->value, $depth + 1))
					. (str_ends_with($tmp, '</div>') ? '' : "\n");
			}

			return $out . '</div>';
		}
	}


	private function copySnapshot(mixed $value): void
	{
		if ($this->collectingMode) {
			return;
		}

		$this->snapshotSelection ??= [];

		if (is_array($value)) {
			foreach ($value as $item) {
				$this->copySnapshot($item->value);
			}
		} elseif ($value instanceof ReferenceNode) {
			if (!isset($this->snapshotSelection[$value->targetId])) {
				$ref = $this->snapshotSelection[$value->targetId] = $this->snapshot[$value->targetId];
				$this->copySnapshot($ref);
			}
		} elseif ($value instanceof CollectionNode && $value->items) {
			foreach ($value->items as $item) {
				$this->copySnapshot($item->value);
			}
		}
	}


	public static function jsonEncode(mixed $value): string
	{
		$old = @ini_set('serialize_precision', '-1'); // @ may be disabled
		try {
			return json_encode($value, JSON_HEX_APOS | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		} finally {
			if ($old !== false) {
				ini_set('serialize_precision', $old);
			}
		}
	}
}
