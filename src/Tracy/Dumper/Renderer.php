<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tracy;


/**
 * Visualisation of internal representation.
 * @internal
 */
class Renderer
{
	/** @var int|bool */
	public $collapseTop = 14;

	/** @var int */
	public $collapseSub = 7;

	/** @var bool */
	public $locationLink = false;

	/** @var bool|null  lazy-loading via JavaScript? true=full, false=none, null=collapsed parts */
	public $lazy;

	/** @var array */
	public $snapshot = [];

	/** @var bool */
	public $collectingMode = false;

	/** @var array|null */
	private $snapshotSelection;

	/** @var array */
	private $parents = [];


	/**
	 * @param  mixed  $model
	 * @tracySkipLocation
	 */
	public function renderHtml($model, array $location = null): string
	{
		$this->parents = [];

		if ($this->lazy === false) { // no lazy-loading
			$html = $this->renderVar($model);
			$model = $snapshot = null;

		} elseif ($this->lazy && (is_array($model) && $model || is_object($model))) { // full lazy-loading
			$html = null;
			$snapshot = $this->collectingMode ? null : $this->snapshot;

		} else { // lazy-loading of collapsed parts
			$html = $this->renderVar($model);
			$snapshot = $this->snapshotSelection;
			$model = $this->snapshotSelection = null;
		}

		[$file, $line, $code] = $location;

		return '<pre class="tracy-dump' . ($model && $this->collapseTop === true ? ' tracy-collapsed' : '') . '"'
			. ($location ? Helpers::formatHtml(' title="%in file % on line %" data-tracy-href="%"', "$code\n", $file, $line, Helpers::editorUri($file, $line)) : null)
			. ($snapshot === null ? '' : ' data-tracy-snapshot=' . Dumper::formatSnapshotAttribute($snapshot))
			. ($model ? " data-tracy-dump='" . json_encode($model, JSON_HEX_APOS | JSON_HEX_AMP) . "'>" : '>')
			. $html
			. ($location && $this->locationLink ? '<small>in ' . Helpers::editorLink($file, $line) . '</small>' : '')
			. "</pre>\n";
	}


	/**
	 * @param  mixed  $model
	 * @tracySkipLocation
	 */
	public function renderText($model, array $location = null, array $colors = []): string
	{
		$this->parents = [];
		$this->lazy = false;
		$s = $this->renderVar($model);
		if ($colors) {
			$s = preg_replace_callback('#<span class="tracy-dump-(\w+)">|</span>#', function ($m) use ($colors): string {
				return "\033[" . (isset($m[1], $colors[$m[1]]) ? $colors[$m[1]] : '0') . 'm';
			}, $s);
		}
		$s = htmlspecialchars_decode(strip_tags($s), ENT_QUOTES);

		if ($this->locationLink && ([$file, $line] = $location)) {
			$s .= "in $file:$line";
		}

		return $s;
	}


	/**
	 * @param  mixed  $model
	 */
	private function renderVar($model, int $depth = 0): string
	{
		switch (true) {
			case $model === null:
				return "<span class=\"tracy-dump-null\">null</span>\n";

			case is_bool($model):
				return '<span class="tracy-dump-bool">' . ($model ? 'true' : 'false') . "</span>\n";

			case is_int($model):
				return "<span class=\"tracy-dump-number\">$model</span>\n";

			case is_float($model):
				return '<span class="tracy-dump-number">' . json_encode($model) . "</span>\n";

			case is_string($model):
				return '<span class="tracy-dump-string">"'
					. Helpers::escapeHtml($model)
					. '"</span>' . (strlen($model) > 1 ? ' (' . strlen($model) . ')' : '') . "\n";

			case is_array($model):
				return $this->renderArray($model, $depth);

			case isset($model->object):
				return $this->renderObject($model, $depth);

			case isset($model->number):
				return '<span class="tracy-dump-number">' . Helpers::escapeHtml($model->number) . "</span>\n";

			case isset($model->key):
				return '<span>' . Helpers::escapeHtml($model->key) . "</span>\n";

			case isset($model->string):
				return '<span class="tracy-dump-string">"'
					. Helpers::escapeHtml($model->string)
					. '"</span>' . ($model->length > 1 ? ' (' . $model->length . ')' : '') . "\n";

			case isset($model->stop):
				return '<span class="tracy-dump-array">array</span> (' . $model->stop[0] . ') ' . ($model->stop[1] ? '[ <i>RECURSION</i> ]' : '[ ... ]') . "\n";

			case isset($model->resource):
				return $this->renderResource($model, $depth);

			default:
				throw new \Exception('Unknown type');
		}
	}


	private function renderArray(array $model, int $depth): string
	{
		$out = '<span class="tracy-dump-array">array</span> (';

		if (empty($model)) {
			return $out . ")\n";
		}

		$collapsed = $depth
			? count($model) >= $this->collapseSub
			: (is_int($this->collapseTop) ? count($model) >= $this->collapseTop : $this->collapseTop);

		$span = '<span class="tracy-toggle' . ($collapsed ? ' tracy-collapsed' : '') . '"';

		if ($collapsed && $this->lazy !== false) {
			$this->copySnapshot($model);
			return $span . " data-tracy-dump='"
				. json_encode($model, JSON_HEX_APOS | JSON_HEX_AMP) . "'>"
				. $out . count($model) . ")</span>\n";
		}

		$out = $span . '>' . $out . count($model) . ")</span>\n" . '<div' . ($collapsed ? ' class="tracy-collapsed"' : '') . '>';
		foreach ($model as $info) {
			$out .= '<span class="tracy-dump-indent">   ' . str_repeat('|  ', $depth) . '</span>'
				. '<span class="tracy-dump-key">' . Helpers::escapeHtml($info[0]) . '</span> => '
				. (isset($info[2]) ? '<span class="tracy-dump-hash">&' . $info[2] . '</span> ' : '')
				. $this->renderVar($info[1], $depth + 1);
		}

		return $out . '</div>';
	}


	private function renderObject(object $model, int $depth): string
	{
		$object = $this->snapshot[$model->object];

		$editorAttributes = '';
		if (isset($object->editor)) {
			$editorAttributes = Helpers::formatHtml(
				' title="Declared in file % on line %" data-tracy-href="%"',
				$object->editor->file,
				$object->editor->line,
				$object->editor->url
			);
		}

		$out = '<span class="tracy-dump-object"' . $editorAttributes . '>'
			. Helpers::escapeHtml($object->name)
			. '</span> <span class="tracy-dump-hash">#' . $model->object . '</span>';

		if (!isset($object->items)) {
			return $out . " { ... }\n";

		} elseif (!$object->items) {
			return $out . "\n";

		} elseif (in_array($model->object, $this->parents, true)) {
			return $out . " { <i>RECURSION</i> }\n";
		}

		$collapsed = $depth
			? count($object->items) >= $this->collapseSub
			: (is_int($this->collapseTop) ? count($object->items) >= $this->collapseTop : $this->collapseTop);

		$span = '<span class="tracy-toggle' . ($collapsed ? ' tracy-collapsed' : '') . '"';

		if ($collapsed && $this->lazy !== false) {
			$this->copySnapshot($model);
			return $span . " data-tracy-dump='"
				. json_encode($model, JSON_HEX_APOS | JSON_HEX_AMP)
				. "'>" . $out . "</span>\n";
		}

		$out = $span . '>' . $out . "</span>\n" . '<div' . ($collapsed ? ' class="tracy-collapsed"' : '') . '>';
		$this->parents[] = $model->object;

		static $classes = [
			Exposer::PROP_PUBLIC => 'tracy-dump-public',
			Exposer::PROP_PROTECTED => 'tracy-dump-protected',
			Exposer::PROP_PRIVATE => 'tracy-dump-private',
			Exposer::PROP_DYNAMIC => 'tracy-dump-dynamic',
			Exposer::PROP_VIRTUAL => 'tracy-dump-virtual',
		];

		foreach ($object->items as $info) {
			$out .= '<span class="tracy-dump-indent">   ' . str_repeat('|  ', $depth) . '</span>'
				. '<span class="' . $classes[$info[2]] . '">' . Helpers::escapeHtml($info[0]) . '</span>'
				. ': '
				. (isset($info[3]) ? '<span class="tracy-dump-hash">&' . $info[3] . '</span> ' : '')
				. $this->renderVar($info[1], $depth + 1);
		}
		array_pop($this->parents);
		return $out . '</div>';
	}


	private function renderResource(object $model, int $depth): string
	{
		$resource = $this->snapshot[$model->resource];
		$out = '<span class="tracy-dump-resource">' . Helpers::escapeHtml($resource->name) . '</span> '
			. '<span class="tracy-dump-hash">@' . substr($model->resource, 1) . '</span>';
		if (isset($resource->items)) {
			$out = "<span class=\"tracy-toggle tracy-collapsed\">$out</span>\n<div class=\"tracy-collapsed\">";
			foreach ($resource->items as [$k, $v]) {
				$out .= '<span class="tracy-dump-indent">   ' . str_repeat('|  ', $depth) . '</span>'
					. '<span class="tracy-dump-key">' . Helpers::escapeHtml($k) . '</span>: ' . $this->renderVar($v, $depth + 1);
			}
			return $out . '</div>';
		}
		return "$out\n";
	}


	private function copySnapshot($model): void
	{
		if ($this->collectingMode) {
			return;
		}
		settype($this->snapshotSelection, 'array');
		if (is_array($model)) {
			foreach ($model as [$k, $v]) {
				$this->copySnapshot($v);
			}
		} elseif (isset($model->object)) {
			$object = $this->snapshotSelection[$model->object] = $this->snapshot[$model->object];
			if (!in_array($model->object, $this->parents, true)) {
				$this->parents[] = $model->object;
				foreach ($object->items ?? [] as [$k, $v]) {
					$this->copySnapshot($v);
				}
				array_pop($this->parents);
			}
		} elseif (isset($model->resource)) {
			$resource = $this->snapshotSelection[$model->resource] = $this->snapshot[$model->resource];
			foreach ($resource->items ?? [] as [$k, $v]) {
				$this->copySnapshot($v);
			}
		}
	}
}
