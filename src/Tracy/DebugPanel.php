<?php

/**
 * This file is part of the Nette Framework.
 *
 * Copyright (c) 2004, 2011 David Grudl (http://davidgrudl.com)
 *
 * This source file is subject to the "Nette license", and/or
 * GPL license. For more information please see http://nette.org
 */

namespace Nette;



/**
 * IDebugPanel implementation helper.
 *
 * @author     David Grudl
 */
class DebugPanel extends Object implements IDebugPanel
{
	private $id;

	private $tabCb;

	private $panelCb;

	public $data;

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
		call_user_func($this->tabCb, $this->id, $this->data);
		return ob_get_clean();
	}

	public function getPanel()
	{
		ob_start();
		call_user_func($this->panelCb, $this->id, $this->data);
		return ob_get_clean();
	}

}
