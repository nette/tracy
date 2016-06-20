<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Tracy;

interface ISession
{

	/**
	 * @return bool
	 */
	public function isActive();


	/**
	 * @return void
	 */
	public function start();

}
