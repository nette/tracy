<?php declare(strict_types=1);

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Tracy;


/**
 * Tracy Bar panel providing a tab label and optional panel content.
 * @method ?string getAgentInfo() Returns markdown summary for AI agents.
 */
interface IBarPanel
{
	/**
	 * Returns HTML for the tab label shown in the Bar.
	 * @return ?string
	 */
	function getTab();

	/**
	 * Returns HTML for the panel popup content, or null to render tab-only.
	 * @return ?string
	 */
	function getPanel();
}
