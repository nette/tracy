<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Tracy;

class DefaultSession implements ISession
{

	/**
	 * @return bool
	 */
	public function isActive()
	{
		return session_status() === PHP_SESSION_ACTIVE;
	}


	/**
	 * @return void
	 */
	public function start()
	{
		ini_set('session.use_cookies', '1');
		ini_set('session.use_only_cookies', '1');
		ini_set('session.use_trans_sid', '0');
		ini_set('session.cookie_path', '/');
		ini_set('session.cookie_httponly', '1');
		session_start();
	}

}
