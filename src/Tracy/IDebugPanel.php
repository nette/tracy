<?php

/**
 * Nette Framework
 *
 * @copyright  Copyright (c) 2004, 2010 David Grudl
 * @license    http://nette.org/license  Nette license
 * @link       http://nette.org
 * @category   Nette
 * @package    Nette
 */

namespace Nette;

use Nette;



/**
 * Custom output for Nette\Debug.
 *
 * @copyright  Copyright (c) 2004, 2010 David Grudl
 * @package    Nette
 */
interface IDebugPanel
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