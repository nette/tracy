<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tracy\BlueScreen;

use Throwable;


/**
 * Callback to Tracy\BlueScreen\Extension adapter.
 * @internal
 */
final class ExtensionAdapter extends Extension
{
	/** @var callable */
	private $callback;

	/** @var bool */
	private $isPanel;

	/** @var ?Panel */
	private $bottomPanel;


	public function __construct(callable $callback, bool $isPanel)
	{
		$this->callback = $callback;
		$this->isPanel = $isPanel;
	}


	public function getTopPanel(Throwable $e): ?Panel
	{
		if (!$this->isPanel) {
			return null;
		}
		$res = ($this->callback)($e);
		if (empty($res['tab']) || empty($res['panel'])) {
			return null;
		}
		return new Panel($res['tab'], $res['panel']);
	}


	public function getMiddlePanel(Throwable $e): ?Panel
	{
		if (!$this->isPanel) {
			return null;
		}
		$res = ($this->callback)(null);
		if (empty($res['tab']) || empty($res['panel'])) {
			return null;
		}
		$panel = new Panel($res['tab'], $res['panel'], !empty($res['collapsed']));
		if (!empty($res['bottom'])) {
			$this->bottomPanel = $panel;
			return null;
		}
		return $panel;
	}


	public function getBottomPanel(Throwable $e): ?Panel
	{
		// requires getMiddlePanel to be called first
		return $this->bottomPanel;
	}


	public function getAction(Throwable $e): ?Action
	{
		if ($this->isPanel) {
			return null;
		}
		$res = ($this->callback)($e);
		if (empty($res['link']) || empty($res['label'])) {
			return null;
		}
		return new Action($res['label'], $res['link'], !empty($res['external']));
	}


	public function getId(): string
	{
		is_callable($this->callback, true, $id);
		return $id;
	}
}
