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
	public $classLocation = false;

	/** @var bool */
	public $sourceLocation = false;

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
		try {
			$value = $model->value;
			$this->snapshot = $model->snapshot;

			if ($this->lazy === false) { // no lazy-loading
				$html = $this->renderVar($value);
				$json = $snapshot = null;

			} elseif ($this->lazy && (is_array($value) && $value || is_object($value))) { // full lazy-loading
				$html = '';
				$snapshot = $this->collectingMode ? null : $this->snapshot;
				$json = $value;

			} else { // lazy-loading of collapsed parts
				$html = $this->renderVar($value);
				$snapshot = $this->snapshotSelection;
				$json = null;
			}
		} finally {
			$this->parents = $this->snapshot = [];
			$this->snapshotSelection = null;
		}

		$location = null;
		if ($model->location && $this->sourceLocation) {
			[$file, $line, $code] = $model->location;
			$location = Helpers::formatHtml(
				' title="%in file % on line %" data-tracy-href="%"', "$code\n",
				$file, $line, Helpers::editorUri($file, $line)
			);
		}

		return '<pre class="tracy-dump' . ($json && $this->collapseTop === true ? ' tracy-collapsed' : '') . '"'
				. $location
				. ($snapshot !== null ? " data-tracy-snapshot='" . self::jsonEncode($snapshot) . "'" : '')
				. ($json ? " data-tracy-dump='" . self::jsonEncode($json) . "'" : '')
				. ($location || strlen($html) > 100 ? "\n" : '')
			. '>'
			. $html
			. ($location ? ($html && substr($html, -6) !== '</div>' ? "\n" : '') . '<small>in ' . Helpers::editorLink($file, $line) . '</small>' : '')
			. "</pre>\n";
	}


	public function renderAsText(\stdClass $model, array $colors = []): string
	{
		try {
			$this->snapshot = $model->snapshot;
			$this->lazy = false;
			$s = $this->renderVar($model->value);
		} finally {
			$this->parents = $this->snapshot = [];
		}

		if ($colors) {
			$s = preg_replace_callback('#<span class="tracy-dump-(\w+)"[^>]*>|</span>#', function ($m) use ($colors): string {
				return "\033[" . (isset($m[1], $colors[$m[1]]) ? $colors[$m[1]] : '0') . 'm';
			}, $s);
		}
		$s = htmlspecialchars_decode(strip_tags($s), ENT_QUOTES | ENT_HTML5);
		$s = str_replace('…', '...', $s);
		$s .= substr($s, -1) === "\n" ? '' : "\n";

		if ($this->sourceLocation && ([$file, $line] = $model->location)) {
			$s .= "in $file:$line\n";
		}

		return $s;
	}


	/**
	 * @param  mixed  $value
	 */
	private function renderVar($value, int $depth = 0, $keyType = null): string
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
				return $this->renderString($value, $keyType);

			case is_array($value):
			case $value->type === 'array':
				return $this->renderArray($value, $depth);

			case $value->type === 'ref':
				return $this->renderVar($this->snapshot[$value->value], $depth);

			case $value->type === 'object':
				return $this->renderObject($value, $depth);

			case $value->type === 'number':
				return '<span class="tracy-dump-number">' . Helpers::escapeHtml($value->value) . '</span>';

			case $value->type === 'text':
				return '<span>' . Helpers::escapeHtml($value->value) . '</span>';

			case $value->type === 'string':
			case $value->type === 'bin':
				return $this->renderString($value, $keyType);

			case $value->type === 'resource':
				return $this->renderResource($value, $depth);

			default:
				throw new \Exception('Unknown type');
		}
	}


	/**
	 * @param  string|Value  $str
	 */
	private function renderString($str, $keyType): string
	{
		if ($keyType === 'array') {
			return '<span class="tracy-dump-string">\''
				. (is_string($str) ? Helpers::escapeHtml($str) : str_replace("\n", "\n ", $str->value))
				. "'</span>";

		} elseif ($keyType !== null) {
			static $classes = [
				Value::PROP_PUBLIC => 'tracy-dump-public',
				Value::PROP_PROTECTED => 'tracy-dump-protected',
				Value::PROP_DYNAMIC => 'tracy-dump-dynamic',
				Value::PROP_VIRTUAL => 'tracy-dump-virtual',
			];
			$title = is_string($keyType) ? ' title="declared in ' . Helpers::escapeHtml($keyType) . '"' : null;
			return '<span class="'
				. ($title ? 'tracy-dump-private' : $classes[$keyType]) . '"' . $title . '>'
				. (is_string($str) ? Helpers::escapeHtml($str) : str_replace("\n", "\n ", $str->value))
				. '</span>';

		} elseif (is_string($str)) {
			$len = strlen(utf8_decode($str));
			return '<span class="tracy-dump-string"'
				. ($len > 1 ? ' title="' . $len . ' characters"' : '')
				. ">'" . Helpers::escapeHtml($str) . "'</span>";

		} else {
			$unit = $str->type === 'string' ? 'characters' : 'bytes';
			return '<span class="tracy-dump-string"'
				. ($str->length > 1 ? " title=\"$str->length $unit\">" : '>')
				. (strpos($str->value, "\n") === false ? '' : "\n   ") . "'"
				. str_replace("\n", "\n    ", $str->value)
				. "'</span>";
		}
	}


	/**
	 * @param  array|Value  $array
	 */
	private function renderArray($array, int $depth): string
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
			}
		}

		if (!$count) {
			return $out;
		}

		$collapsed = $depth
			? $count >= $this->collapseSub
			: (is_int($this->collapseTop) ? $count >= $this->collapseTop : $this->collapseTop);

		$span = '<span class="tracy-toggle' . ($collapsed ? ' tracy-collapsed' : '') . '"';

		if ($collapsed && $this->lazy !== false) {
			$array = isset($array->id) ? new Value('ref', $array->id) : $array;
			$this->copySnapshot($array);
			return $span . " data-tracy-dump='" . self::jsonEncode($array) . "'>" . $out . '</span>';
		}

		$out = $span . '>' . $out . "</span>\n" . '<div' . ($collapsed ? ' class="tracy-collapsed"' : '') . '>';
		$indent = '<span class="tracy-dump-indent">   ' . str_repeat('|  ', $depth) . '</span>';
		$this->parents[$array->id ?? null] = true;

		foreach ($items as $info) {
			[$k, $v, $ref] = $info + [2 => null];
			$out .= $indent
				. $this->renderVar($k, $depth + 1, 'array')
				. ' => '
				. ($ref ? '<span class="tracy-dump-hash">&' . $ref . '</span> ' : '')
				. ($tmp = $this->renderVar($v, $depth + 1))
				. (substr($tmp, -6) === '</div>' ? '' : "\n");
		}

		if ($count > count($items)) {
			$out .= $indent . "…\n";
		}
		unset($this->parents[$array->id ?? null]);
		return $out . '</div>';
	}


	private function renderObject(Value $object, int $depth): string
	{
		$editorAttributes = '';
		if ($this->classLocation && $object->editor) {
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
			return $out . ' …';

		} elseif (!$object->items) {
			return $out;

		} elseif (isset($this->parents[$object->id])) {
			return $out . ' <i>RECURSION</i>';
		}

		$collapsed = $depth
			? count($object->items) >= $this->collapseSub
			: (is_int($this->collapseTop) ? count($object->items) >= $this->collapseTop : $this->collapseTop);

		$span = '<span class="tracy-toggle' . ($collapsed ? ' tracy-collapsed' : '') . '"';

		if ($collapsed && $this->lazy !== false) {
			$ref = new Value('ref', $object->id);
			$this->copySnapshot($ref);
			return $span . " data-tracy-dump='" . json_encode($ref) . "'>" . $out . '</span>';
		}

		$out = $span . '>' . $out . "</span>\n" . '<div' . ($collapsed ? ' class="tracy-collapsed"' : '') . '>';
		$indent = '<span class="tracy-dump-indent">   ' . str_repeat('|  ', $depth) . '</span>';
		$this->parents[$object->id] = true;

		foreach ($object->items as $info) {
			[$k, $v, $type, $ref] = $info + [2 => Value::PROP_VIRTUAL, null];
			$out .= $indent
				. $this->renderVar($k, $depth + 1, $type)
				. ': '
				. ($ref ? '<span class="tracy-dump-hash">&' . $ref . '</span> ' : '')
				. ($tmp = $this->renderVar($v, $depth + 1))
				. (substr($tmp, -6) === '</div>' ? '' : "\n");
		}

		if ($object->length > count($object->items)) {
			$out .= $indent . "…\n";
		}
		unset($this->parents[$object->id]);
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
					. $this->renderVar($k, $depth + 1, Value::PROP_VIRTUAL)
					. ': '
					. ($tmp = $this->renderVar($v, $depth + 1))
					. (substr($tmp, -6) === '</div>' ? '' : "\n");
			}
			return $out . '</div>';
		}
		return $out;
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
			if (!isset($this->parents[$value->value])) {
				$this->parents[$value->value] = true;
				$this->copySnapshot($ref);
				unset($this->parents[$value->value]);
			}
		} elseif ($value instanceof Value && $value->items) {
			foreach ($value->items as [, $v]) {
				$this->copySnapshot($v);
			}
		}
	}


	public static function jsonEncode($snapshot): string
	{
		return json_encode($snapshot, JSON_HEX_APOS | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
	}
}
