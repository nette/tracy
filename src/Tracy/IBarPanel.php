<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Tracy;


/**
 * Custom output for Debugger.
 */
interface IBarPanel
{

	/**
	 * Renders HTML code for custom tab.
	 */
	function getTab(): ?string;

	/**
	 * Renders HTML code for custom panel.
	 */
	function getPanel(): ?string;
}
