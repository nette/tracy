<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

use Nette\Diagnostics\Debugger;



/**
 * Nette\Diagnostics\Debugger::enable() shortcut.
 */
function debug()
{
	Debugger::$strictMode = TRUE;
	Debugger::enable(Debugger::DEVELOPMENT);
}



/**
 * Nette\Diagnostics\Debugger::dump() shortcut.
 */
function dump($var)
{
	foreach (func_get_args() as $arg) {
		Debugger::dump($arg);
	}
	return $var;
}



/**
 * Nette\Diagnostics\Debugger::log() shortcut.
 */
function dlog($var = NULL)
{
	if (func_num_args() === 0) {
		Debugger::log(new Exception, 'dlog');
	}
	foreach (func_get_args() as $arg) {
		Debugger::log($arg, 'dlog');
	}
	return $var;
}
