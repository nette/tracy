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

	/** @var Value[] */
	private $snapshot = [];

	/** @var Value[]|null */
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
				return $this->renderArray($value, $depth);

			case $value->type === 'ref':
				return $this->renderVar($this->snapshot[$value->value], $depth);

			case $value->type === 'object':
				return $this->renderObject($value, $depth);

			case $value->type === 'number':
				return '<span class="tracy-dump-number">' . Helpers::escapeHtml($value->value) . "</span>\n";

			case $value->type === 'text':
				return '<span>' . Helpers::escapeHtml($value->value) . "</span>\n";

			case $value->type === 'string':
				return $this->renderString($value);

			case $value->type === 'stop':
				return '<span class="tracy-dump-array">array</span> (' . $value->value[0] . ') ' . ($value->value[1] ? '[ <i>RECURSION</i> ]' : '[ ... ]') . "\n";

			case $value->type === 'resource':
				return $this->renderResource($value, $depth);

			default:
				throw new \Exception('Unknown type');
		}
	}


	/**
	 * @param  string|Value  $str
	 */
	private function renderString($str): string
	{
		if (is_string($str)) {
			return '<span class="tracy-dump-string">"'
				. Helpers::escapeHtml($str)
				. '"</span>' . (strlen($str) > 1 ? ' (' . strlen($str) . ')' : '') . "\n";
		} else {
			return '<span class="tracy-dump-string">"'
				. Helpers::escapeHtml($str->value)
				. '"</span>' . ($str->length > 1 ? ' (' . $str->length . ')' : '') . "\n";
		}
	}


	private function renderArray(array $array, int $depth): string
	{
		$out = '<span class="tracy-dump-array">array</span> (';

		if (empty($array)) {
			return $out . ")\n";
		}

		$collapsed = $depth
			? count($array) >= $this->collapseSub
			: (is_int($this->collapseTop) ? count($array) >= $this->collapseTop : $this->collapseTop);

		$span = '<span class="tracy-toggle' . ($collapsed ? ' tracy-collapsed' : '') . '"';

		if ($collapsed && $this->lazy !== false) {
			$this->copySnapshot($array);
			return $span . " data-tracy-dump='"
				. json_encode($array, JSON_HEX_APOS | JSON_HEX_AMP) . "'>"
				. $out . count($array) . ")</span>\n";
		}

		$out = $span . '>' . $out . count($array) . ")</span>\n" . '<div' . ($collapsed ? ' class="tracy-collapsed"' : '') . '>';

		foreach ($array as $info) {
			[$k, $v, $ref] = $info + [2 => null];
			$out .= '<span class="tracy-dump-indent">   ' . str_repeat('|  ', $depth) . '</span>'
				. '<span class="tracy-dump-key">' . Helpers::escapeHtml($k) . '</span> => '
				. ($ref ? '<span class="tracy-dump-hash">&' . $ref . '</span> ' : '')
				. $this->renderVar($v, $depth + 1);
		}

		return $out . '</div>';
	}


	private function renderObject(Value $object, int $depth): string
	{
		$editorAttributes = '';
		if ($this->locationClass && $object->editor) {
			$editorAttributes = Helpers::formatHtml(
				' title="Declared in file % on line %" data-tracy-href="%"',
				$object->editor->file,
				$object->editor->line,
				$object->editor->url
			);
		}

		$out = '<span class="tracy-dump-object"' . $editorAttributes . '>'
			. Helpers::escapeHtml($object->value)
			. '</span> <span class="tracy-dump-hash">#' . $object->id . '</span>';

		if ($object->items === null) {
			return $out . " { ... }\n";

		} elseif (!$object->items) {
			return $out . "\n";

		} elseif (in_array($object->id, $this->parents, true)) {
			return $out . " { <i>RECURSION</i> }\n";
		}

		$collapsed = $depth
			? count($object->items) >= $this->collapseSub
			: (is_int($this->collapseTop) ? count($object->items) >= $this->collapseTop : $this->collapseTop);

		$span = '<span class="tracy-toggle' . ($collapsed ? ' tracy-collapsed' : '') . '"';

		if ($collapsed && $this->lazy !== false) {
			$ref = new Value('ref', $object->id);
			$this->copySnapshot($ref);
			return $span . " data-tracy-dump='" . json_encode($ref) . "'>" . $out . "</span>\n";
		}

		$out = $span . '>' . $out . "</span>\n" . '<div' . ($collapsed ? ' class="tracy-collapsed"' : '') . '>';
		$this->parents[] = $object->id;

		foreach ($object->items as $info) {
			[$k, $v, $type, $ref] = $info + [3 => null];
			$out .= '<span class="tracy-dump-indent">   ' . str_repeat('|  ', $depth) . '</span>'
				. '<span class="tracy-dump-key">' . Helpers::escapeHtml($k) . '</span>'
				. ($type ? ' <span class="tracy-dump-visibility">' . ($type === 1 ? 'protected' : 'private') . '</span>' : '')
				. ' => '
				. ($ref ? '<span class="tracy-dump-hash">&' . $ref . '</span> ' : '')
				. $this->renderVar($v, $depth + 1);
		}
		array_pop($this->parents);
		return $out . '</div>';
	}


	private function renderResource(Value $resource, int $depth): string
	{
		$out = '<span class="tracy-dump-resource">' . Helpers::escapeHtml($resource->value) . '</span> '
			. '<span class="tracy-dump-hash">@' . substr($resource->id, 1) . '</span>';
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
			foreach ($value as [, $v]) {
				$this->copySnapshot($v);
			}
		} elseif ($value instanceof Value && $value->type === 'ref') {
			$ref = $this->snapshotSelection[$value->value] = $this->snapshot[$value->value];
			if (!in_array($value->value, $this->parents, true)) {
				$this->parents[] = $value->value;
				$this->copySnapshot($ref);
				array_pop($this->parents);
			}
		} elseif ($value instanceof Value && $value->items) {
			foreach ($value->items as [, $v]) {
				$this->copySnapshot($v);
			}
		}
	}


	public static function formatSnapshotAttribute(array $snapshot): string
	{
		return "'" . json_encode($snapshot, JSON_HEX_APOS | JSON_HEX_AMP) . "'";
	}
}
