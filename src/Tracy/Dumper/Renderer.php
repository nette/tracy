<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tracy\Dumper;

use Tracy\Helpers;


/**
 * Visualisation of internal representation.
 * @internal
 */
final class Renderer
{
	/** @var int|bool */
	public $collapseTop = 14;

	/** @var int */
	public $collapseSub = 7;

	/** @var bool */
	public $locationLink = false;

	/** @var bool */
	public $locationClass = false;

	/** @var bool */
	public $locationSource = false;

	/** @var bool|null  lazy-loading via JavaScript? true=full, false=none, null=collapsed parts */
	public $lazy;

	/** @var bool */
	public $collectingMode = false;

	/** @var Structure[] */
	private $snapshot = [];

	/** @var Structure[]|null */
	private $snapshotSelection;

	/** @var array */
	private $parents = [];

	/** @var array */
	private $above = [];


	public function renderAsHtml(\stdClass $model): string
	{
		$this->parents = $this->above = [];
		$this->snapshot = $model->snapshot;
		$value = $model->value;

		if (!$this->locationClass) {
			foreach ($this->snapshot as $obj) {
				$obj->editor = null;
			}
		}

		if ($this->lazy === false) { // no lazy-loading
			$html = $this->renderVar($value);
			$value = $snapshot = null;

		} elseif ($this->lazy && (is_array($value) && $value || is_object($value))) { // full lazy-loading
			$html = null;
			$snapshot = $this->collectingMode ? null : $this->snapshot;

		} else { // lazy-loading of collapsed parts
			$html = $this->renderVar($value);
			$snapshot = $this->snapshotSelection;
			$value = $this->snapshotSelection = null;
		}

		[$file, $line, $code] = $model->location;
		$uri = $model->location ? Helpers::editorUri($file, $line) : null;

		return '<pre class="tracy-dump' . ($value && $this->collapseTop === true ? ' tracy-collapsed' : '') . '"'
			. ($model->location && $this->locationSource ? Helpers::formatHtml(' title="%in file % on line %%" data-tracy-href="%"', "$code\n", $file, $line, $uri ? "\nCtrl-Click to open in editor" : '', $uri) : null)
			. ($snapshot === null ? '' : ' data-tracy-snapshot=' . self::formatSnapshotAttribute($snapshot))
			. ($value ? " data-tracy-dump='" . json_encode($value, JSON_HEX_APOS | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) . "'>" : '>')
			. $html
			. ($model->location && $this->locationLink ? ($html && substr($html, -6) !== '</div>' ? "\n" : '') . '<small>in ' . Helpers::editorLink($file, $line) . '</small>' : '')
			. "</pre>\n";
	}


	public function renderAsText(\stdClass $model, array $colors = []): string
	{
		$this->snapshot = $model->snapshot;
		$this->parents = [];
		$this->lazy = false;
		$s = $this->renderVar($model->value);
		if ($colors) {
			$s = preg_replace_callback('#<span class="tracy-dump-(\w+)"[^>]*>|</span>#', function ($m) use ($colors): string {
				return "\033[" . (isset($m[1], $colors[$m[1]]) ? $colors[$m[1]] : '0') . 'm';
			}, $s);
		}
		$s = htmlspecialchars_decode(strip_tags($s), ENT_QUOTES | ENT_HTML5);
		$s = str_replace('…', '...', $s);
		$s .= substr($s, -1) === "\n" ? '' : "\n";

		if ($this->locationLink && ([$file, $line] = $model->location)) {
			$s .= "in $file:$line\n";
		}

		return $s;
	}


	/**
	 * @param  mixed  $value
	 */
	private function renderVar($value, int $depth = 0, bool $isKey = false): string
	{
		switch (true) {
			case $value === null:
				return '<span class="tracy-dump-null">null</span>';

			case is_bool($value):
				return '<span class="tracy-dump-bool">' . ($value ? 'true' : 'false') . '</span>';

			case is_int($value):
				return '<span class="tracy-dump-number">' . $value . '</span>';

			case is_float($value):
				return '<span class="tracy-dump-number">' . json_encode($value) . '</span>';

			case is_string($value):
				return $this->renderString($value, $isKey);

			case is_array($value):
			case $value->type === $value::TYPE_ARRAY:
				return $this->renderArray($value, $depth);

			case $value->type === $value::TYPE_OBJECT:
				return $this->renderObject($value, $depth);

			case $value->type === $value::TYPE_NUMBER:
				return '<span class="tracy-dump-number">' . Helpers::escapeHtml($value->value) . '</span>';

			case $value->type === $value::TYPE_TEXT:
				return '<span>' . Helpers::escapeHtml($value->value) . '</span>';

			case $value->type === $value::TYPE_STRING:
			case $value->type === $value::TYPE_BINARY:
				return $this->renderString($value, $isKey);

			case $value->type === $value::TYPE_STOP:
				return '<span class="tracy-dump-array">array</span> (' . $value->value . ') …';

			case $value->type === $value::TYPE_RESOURCE:
				return $this->renderResource($value, $depth);

			default:
				throw new \Exception('Unknown type');
		}
	}


	/**
	 * @param  string|Value  $value
	 */
	private function renderString($value, bool $isKey): string
	{
		if ($isKey) {
			return '<span class="tracy-dump-string">\'' . str_replace("\n", "\n ", is_string($value) ? $value : $value->value) . "'</span>";

		} elseif (is_string($value)) {
			$len = strlen(utf8_decode($value));
			return '<span class="tracy-dump-string"'
				. ($len > 1 ? ' title="' . $len . ' characters"' : '')
				. ">'$value'</span>";

		} else {
			return '<span class="tracy-dump-string"'
				. ($value->length > 1 ? ' title="' . $value->length . ' ' . ($value->type === $value::TYPE_STRING ? 'characters' : 'bytes') . '">' : '>')
				. (strpos($value->value, "\n") === false ? '' : "\n   ") . "'"
				. str_replace("\n", "\n    ", $value->value)
				. "'</span>";
		}
	}


	/**
	 * @param  array|Value  $value
	 */
	private function renderArray($value, int $depth): string
	{
		$out = '<span class="tracy-dump-array">array</span> (';

		if (is_array($value)) {
			$items = $value;
			$count = count($value);
			$out .= $count . ')';
		} else {
			$struct = $this->snapshot[$value->value];
			if (!isset($struct->items)) {
				return $out . $struct->length . ') …';
			}
			$items = $struct->items;
			$count = $struct->length ?? count($items);
			$out .= $count . ')';
			if (in_array($value->value, $this->parents, true)) {
				return $out . ' <i>RECURSION</i>';

			} elseif (in_array($value->value, $this->above, true)) {
				if ($this->lazy !== false) {
					$this->copySnapshot($value);
					return '<span class="tracy-toggle tracy-collapsed" data-tracy-dump=\'' . json_encode($value) . "'>" . $out . "</span>\n";
				}
				return $out . ' <i>see above</i>';
			}
		}

		if (!$items) {
			return $out;
		}

		$collapsed = $depth
			? $count >= $this->collapseSub
			: (is_int($this->collapseTop) ? $count >= $this->collapseTop : $this->collapseTop);

		$span = '<span class="tracy-toggle' . ($collapsed ? ' tracy-collapsed' : '') . '"';

		if ($collapsed && $this->lazy !== false) {
			$this->copySnapshot($value);
			return $span . " data-tracy-dump='"
				. json_encode($value, JSON_HEX_APOS | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) . "'>"
				. $out . '</span>';
		}

		$out = $span . '>' . $out . "</span>\n" . '<div' . ($collapsed ? ' class="tracy-collapsed"' : '') . '>';
		$fill = [2 => null];
		$indent = '<span class="tracy-dump-indent">   ' . str_repeat('|  ', $depth) . '</span>';
		$this->parents[] = $this->above[] = is_object($value) ? $value->value : null;

		foreach ($items as $info) {
			[$k, $v, $ref] = $info + $fill;
			$out .= $indent
				. $this->renderVar($k, $depth + 1, true)
				. ' => '
				. ($ref ? '<span class="tracy-dump-hash">&' . $ref . '</span> ' : '')
				. ($tmp = $this->renderVar($v, $depth + 1))
				. (substr($tmp, -6) === '</div>' ? '' : "\n");
		}

		if ($count !== count($items)) {
			$out .= $indent . "…\n";
		}
		array_pop($this->parents);
		return $out . '</div>';
	}


	private function renderObject(Value $value, int $depth): string
	{
		$object = $this->snapshot[$value->value];

		$editorAttributes = '';
		if ($this->locationClass && isset($object->editor)) {
			$editorAttributes = Helpers::formatHtml(
				' title="Declared in file % on line %%" data-tracy-href="%"',
				$object->editor->file,
				$object->editor->line,
				$object->editor->url ? "\nCtrl-Click to open in editor" : '',
				$object->editor->url
			);
		}

		$out = '<span class="tracy-dump-object"' . $editorAttributes . '>'
			. Helpers::escapeHtml($object->name)
			. '</span> <span class="tracy-dump-hash">#' . $value->value . '</span>';

		if (!isset($object->items)) {
			return $out . ' …';

		} elseif (!$object->items) {
			return $out;

		} elseif (in_array($value->value, $this->parents, true)) {
			return $out . ' <i>RECURSION</i>';

		} elseif (in_array($value->value, $this->above, true)) {
			if ($this->lazy !== false) {
				$this->copySnapshot($value);
				return '<span class="tracy-toggle tracy-collapsed" data-tracy-dump=\'' . json_encode($value) . "'>" . $out . "</span>\n";
			}
			return $out . ' <i>see above</i>';
		}

		$collapsed = $depth
			? count($object->items) >= $this->collapseSub
			: (is_int($this->collapseTop) ? count($object->items) >= $this->collapseTop : $this->collapseTop);

		$span = '<span class="tracy-toggle' . ($collapsed ? ' tracy-collapsed' : '') . '"';

		if ($collapsed && $this->lazy !== false) {
			$this->copySnapshot($value);
			return $span . " data-tracy-dump='" . json_encode($value) . "'>" . $out . '</span>';
		}

		$out = $span . '>' . $out . "</span>\n" . '<div' . ($collapsed ? ' class="tracy-collapsed"' : '') . '>';
		$indent = '<span class="tracy-dump-indent">   ' . str_repeat('|  ', $depth) . '</span>';
		$this->parents[] = $this->above[] = $value->value;
		$fill = [3 => null];

		static $classes = [
			Exposer::PROP_PUBLIC => 'tracy-dump-public',
			Exposer::PROP_PROTECTED => 'tracy-dump-protected',
			Exposer::PROP_DYNAMIC => 'tracy-dump-dynamic',
			Exposer::PROP_VIRTUAL => 'tracy-dump-virtual',
		];

		foreach ($object->items as $info) {
			[$k, $v, $type, $ref] = $info + $fill;
			$title = is_string($type) ? ' title="declared in ' . Helpers::escapeHtml($type) . '"' : null;
			$out .= $indent
				. '<span class="' . ($title ? 'tracy-dump-private' : $classes[$type]) . '"' . $title . '>' . str_replace("\n", "\n ", $k) . '</span>'
				. ': '
				. ($ref ? '<span class="tracy-dump-hash">&' . $ref . '</span> ' : '')
				. ($tmp = $this->renderVar($v, $depth + 1))
				. (substr($tmp, -6) === '</div>' ? '' : "\n");
		}

		if (isset($object->length) && $object->length !== count($object->items)) {
			$out .= $indent . "…\n";
		}
		array_pop($this->parents);
		return $out . '</div>';
	}


	private function renderResource(Value $value, int $depth): string
	{
		$struct = $this->snapshot[$value->value];
		$out = '<span class="tracy-dump-resource">' . Helpers::escapeHtml($struct->name) . '</span> '
			. '<span class="tracy-dump-hash">@' . substr($value->value, 1) . '</span>';

		if (!$struct->items) {
			return $out;

		} elseif (in_array($value->value, $this->above, true)) {
			if ($this->lazy !== false) {
				$this->copySnapshot($value);
				return '<span class="tracy-toggle tracy-collapsed" data-tracy-dump=\'' . json_encode($value) . "'>" . $out . "</span>\n";
			}
			return $out . ' <i>see above</i>';
		}

		$this->above[] = $value->value;
		$out = "<span class=\"tracy-toggle tracy-collapsed\">$out</span>\n<div class=\"tracy-collapsed\">";
		foreach ($struct->items as [$k, $v]) {
			$out .= '<span class="tracy-dump-indent">   ' . str_repeat('|  ', $depth) . '</span>'
				. '<span class="tracy-dump-virtual">' . $k . '</span>: '
				. ($tmp = $this->renderVar($v, $depth + 1))
				. (substr($tmp, -6) === '</div>' ? '' : "\n");
		}
		return $out . '</div>';
	}


	private function copySnapshot($value): void
	{
		if ($this->collectingMode) {
			return;
		}
		settype($this->snapshotSelection, 'array');
		if (is_array($value)) {
			foreach ($value as [$k, $v]) {
				$this->copySnapshot($v);
			}
		} elseif ($value instanceof Value && in_array($value->type, ['object', 'resource', 'array'], true)) {
			$snap = $this->snapshotSelection[$value->value] = $this->snapshot[$value->value];
			if (!in_array($value->value, $this->parents, true)) {
				$this->parents[] = $value->value;
				foreach ($snap->items ?? [] as [$k, $v]) {
					$this->copySnapshot($v);
				}
				array_pop($this->parents);
			}
		}
	}


	public static function formatSnapshotAttribute(array $snapshot): string
	{
		return "'" . json_encode($snapshot, JSON_HEX_APOS | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) . "'";
	}
}
