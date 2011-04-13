<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004, 2011 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Nette\Diagnostics;

use Nette;



/**
 * Custom output for Debugger.
 *
 * @author     David Grudl
 */
interface IPanel
{

	/**
	 * Renders HTML code for custom tab.
	 * @return void
	 */
	function getTab();

	/**
	 * Renders HTML code for custom panel.
	 * @return void
	 */
	function getPanel();

	/**
	 * Returns panel ID.
	 * @return string
	 */
	function getId();

}