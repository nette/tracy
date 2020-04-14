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


	public function renderAsHtml(\stdClass $model): string
	{
		$this->parents = [];
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

		return '<pre class="tracy-dump' . ($value && $this->collapseTop === true ? ' tracy-collapsed' : '') . '"'
			. ($model->location && $this->locationSource ? Helpers::formatHtml(' title="%in file % on line %" data-tracy-href="%"', "$code\n", $file, $line, Helpers::editorUri($file, $line)) : null)
			. ($snapshot === null ? '' : ' data-tracy-snapshot=' . self::formatSnapshotAttribute($snapshot))
			. ($value ? " data-tracy-dump='" . json_encode($value, JSON_HEX_APOS | JSON_HEX_AMP) . "'>" : '>')
			. $html
			. ($model->location && $this->locationLink ? '<small>in ' . Helpers::editorLink($file, $line) . '</small>' : '')
			. "</pre>\n";
	}


	public function renderAsText(\stdClass $model, array $colors = []): string
	{
		$this->snapshot = $model->snapshot;
		$this->parents = [];
		$this->lazy = false;
		$s = $this->renderVar($model->value);
		if ($colors) {
			$s = preg_replace_callback('#<span class="tracy-dump-(\w+)">|</span>#', function ($m) use ($colors): string {
				return "\033[" . (isset($m[1], $colors[$m[1]]) ? $colors[$m[1]] : '0') . 'm';
			}, $s);
		}
		$s = htmlspecialchars_decode(strip_tags($s), ENT_QUOTES);

		if ($this->locationLink && ([$file, $line] = $model->location)) {
			$s .= "in $file:$line";
		}

		return $s;
	}


	/**
	 * @param  mixed  $value
	 */
	private function renderVar($value, int $depth = 0): string
	{
		switch (true) {
			case $value === null:
				return "<span class=\"tracy-dump-null\">null</span>\n";

			case is_bool($value):
				return '<span class="tracy-dump-bool">' . ($value ? 'true' : 'false') . "</span>\n";

			case is_int($value):
				return "<span class=\"tracy-dump-number\">$value</span>\n";

			case is_float($value):
				return '<span class="tracy-dump-number">' . json_encode($value) . "</span>\n";

			case is_string($value):
				return $this->renderString($value);

			case is_array($value):
			case $value->type === 'array':
				return $this->renderArray($value, $depth);

			case $value->type === 'object':
				return $this->renderObject($value, $depth);

			case $value->type === 'number':
				return '<span class="tracy-dump-number">' . Helpers::escapeHtml($value->value) . "</span>\n";

			case $value->type === 'text':
				return '<span>' . Helpers::escapeHtml($value->value) . "</span>\n";

			case $value->type === 'string':
				return $this->renderString($value);

			case $value->type === 'stop':
				return '<span class="tracy-dump-array">array</span> (' . $value->value . ") [ ... ]\n";

			case $value->type === 'resource':
				return $this->renderResource($value, $depth);

			default:
				throw new \Exception('Unknown type');
		}
	}


	/**
	 * @param  string|Value  $value
	 */
	private function renderString($value): string
	{
		if (is_string($value)) {
			return '<span class="tracy-dump-string">"'
				. Helpers::escapeHtml($value)
				. '"</span>' . (strlen($value) > 1 ? ' (' . strlen($value) . ')' : '') . "\n";
		} else {
			return '<span class="tracy-dump-string">"'
				. Helpers::escapeHtml($value->value)
				. '"</span>' . ($value->length > 1 ? ' (' . $value->length . ')' : '') . "\n";
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
		} else {
			$struct = $this->snapshot[$value->value];
			if (!isset($struct->items)) {
				return $out . $struct->length . ") [ ... ]\n";
			}
			$items = $struct->items;
			if (in_array($value->value, $this->parents, true)) {
				return $out . count($items) . ") [ <i>RECURSION</i> ]\n";
			}
		}

		$count = count($items);
		if (!$count) {
			return $out . ")\n";
		}

		$collapsed = $depth
			? $count >= $this->collapseSub
			: (is_int($this->collapseTop) ? $count >= $this->collapseTop : $this->collapseTop);

		$span = '<span class="tracy-toggle' . ($collapsed ? ' tracy-collapsed' : '') . '"';

		if ($collapsed && $this->lazy !== false) {
			$this->copySnapshot($value);
			return $span . " data-tracy-dump='"
				. json_encode($value, JSON_HEX_APOS | JSON_HEX_AMP) . "'>"
				. $out . $count . ")</span>\n";
		}

		$out = $span . '>' . $out . $count . ")</span>\n" . '<div' . ($collapsed ? ' class="tracy-collapsed"' : '') . '>';
		$fill = [2 => null];
		$this->parents[] = is_object($value) ? $value->value : null;

		foreach ($items as $info) {
			[$k, $v, $ref] = $info + $fill;
			$out .= '<span class="tracy-dump-indent">   ' . str_repeat('|  ', $depth) . '</span>'
				. '<span class="tracy-dump-key">' . Helpers::escapeHtml($k) . '</span> => '
				. ($ref ? '<span class="tracy-dump-hash">&' . $ref . '</span> ' : '')
				. $this->renderVar($v, $depth + 1);
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
				' title="Declared in file % on line %" data-tracy-href="%"',
				$object->editor->file,
				$object->editor->line,
				$object->editor->url
			);
		}

		$out = '<span class="tracy-dump-object"' . $editorAttributes . '>'
			. Helpers::escapeHtml($object->name)
			. '</span> <span class="tracy-dump-hash">#' . $value->value . '</span>';

		if (!isset($object->items)) {
			return $out . " { ... }\n";

		} elseif (!$object->items) {
			return $out . "\n";

		} elseif (in_array($value->value, $this->parents, true)) {
			return $out . " { <i>RECURSION</i> }\n";
		}

		$collapsed = $depth
			? count($object->items) >= $this->collapseSub
			: (is_int($this->collapseTop) ? count($object->items) >= $this->collapseTop : $this->collapseTop);

		$span = '<span class="tracy-toggle' . ($collapsed ? ' tracy-collapsed' : '') . '"';

		if ($collapsed && $this->lazy !== false) {
			$this->copySnapshot($value);
			return $span . " data-tracy-dump='"
				. json_encode($value, JSON_HEX_APOS | JSON_HEX_AMP)
				. "'>" . $out . "</span>\n";
		}

		$out = $span . '>' . $out . "</span>\n" . '<div' . ($collapsed ? ' class="tracy-collapsed"' : '') . '>';
		$this->parents[] = $value->value;
		$fill = [3 => null];

		static $classes = [
			Exposer::PROP_PUBLIC => 'tracy-dump-public',
			Exposer::PROP_PROTECTED => 'tracy-dump-protected',
			Exposer::PROP_PRIVATE => 'tracy-dump-private',
			Exposer::PROP_DYNAMIC => 'tracy-dump-dynamic',
		];

		foreach ($object->items as $info) {
			[$k, $v, $type, $ref] = $info + $fill;
			$out .= '<span class="tracy-dump-indent">   ' . str_repeat('|  ', $depth) . '</span>'
				. '<span class="' . $classes[$type] . '">' . Helpers::escapeHtml($k) . '</span>'
				. ' => '
				. ($ref ? '<span class="tracy-dump-hash">&' . $ref . '</span> ' : '')
				. $this->renderVar($v, $depth + 1);
		}
		array_pop($this->parents);
		return $out . '</div>';
	}


	private function renderResource(Value $value, int $depth): string
	{
		$resource = $this->snapshot[$value->value];
		$out = '<span class="tracy-dump-resource">' . Helpers::escapeHtml($resource->name) . '</span> '
			. '<span class="tracy-dump-hash">@' . substr($value->value, 1) . '</span>';
		if ($resource->items) {
			$out = "<span class=\"tracy-toggle tracy-collapsed\">$out</span>\n<div class=\"tracy-collapsed\">";
			foreach ($resource->items as [$k, $v]) {
				$out .= '<span class="tracy-dump-indent">   ' . str_repeat('|  ', $depth) . '</span>'
					. '<span class="tracy-dump-key">' . Helpers::escapeHtml($k) . '</span> => ' . $this->renderVar($v, $depth + 1);
			}
			return $out . '</div>';
		}
		return "$out\n";
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
		return "'" . json_encode($snapshot, JSON_HEX_APOS | JSON_HEX_AMP) . "'";
	}
}
