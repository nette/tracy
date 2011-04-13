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
 * IDebugPanel implementation helper.
 *
 * @author     David Grudl
 */
class Panel extends Nette\Object implements IPanel
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
