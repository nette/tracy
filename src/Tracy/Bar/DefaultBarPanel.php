<?php declare(strict_types=1);

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Tracy;


/**
 * Built-in IBarPanel implementation backed by compiled .phtml templates.
 * @internal
 */
#[\AllowDynamicProperties]
class DefaultBarPanel implements IBarPanel
{
	public mixed $data = null;


	public function __construct(
		private readonly string $id,
	) {
	}


	public function getTab(): string
	{
		return Helpers::capture(function () {
			$data = $this->data;
			require __DIR__ . "/dist/{$this->id}.tab.phtml";
		});
	}


	public function getPanel(): string
	{
		return Helpers::capture(function () {
			if (is_file(__DIR__ . "/dist/{$this->id}.panel.phtml")) {
				$data = $this->data;
				require __DIR__ . "/dist/{$this->id}.panel.phtml";
			}
		});
	}


	public function getAgentInfo(): ?string
	{
		return is_file(__DIR__ . "/dist/{$this->id}.agent.phtml")
			? Helpers::capture(function () {
				$data = $this->data;
				require __DIR__ . "/dist/{$this->id}.agent.phtml";
			})
			: null;
	}
}
