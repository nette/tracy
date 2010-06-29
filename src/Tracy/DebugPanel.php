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



/**
 * IDebugPanel implementation helper.
 *
 * @copyright  Copyright (c) 2004, 2010 David Grudl
 * @package    Nette
 */
class DebugPanel extends Object implements IDebugPanel
{
	private $id;

	private $tabCb;

	private $panelCb;

	public function __construct($id, $tabCb, $panelCb)
	{
		$this->id = $id;
		$this->tabCb = $tabCb;
		$this->panelCb = $panelCb;
	}

	public function getId()
	{
		return $this->id;
	}

	public function getTab()
	{
		ob_start();
		call_user_func($this->tabCb, $this->id);
		return ob_get_clean();
	}

	public function getPanel()
	{
		ob_start();
		call_user_func($this->panelCb, $this->id);
		return ob_get_clean();
	}

}
