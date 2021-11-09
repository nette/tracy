<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tracy\Bar;

use Tracy\IBarPanel;


/**
 * Tracy\IBarPanel to Tracy\Bar\Extension adapter.
 * @internal
 */
final class ExtensionAdapter extends Extension
{
	/** @var IBarPanel */
	private $barPanel;

	/** @var string */
	private $id;


	public function __construct(IBarPanel $barPanel, string $id)
	{
		$this->barPanel = $barPanel;
		$this->id = $id;
	}


	public function getPanel(): ?Panel
	{
		$tab = $this->barPanel->getTab();
		return $tab
			? new Panel($tab, $this->barPanel->getPanel(), $this->id)
			: null;
	}


	public function getId(): string
	{
		return $this->id;
	}
}
