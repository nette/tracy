<?php

/**
 * This file is part of the Tracy (http://tracy.nette.org)
 *
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 *
 * For the full copyright and license information, please view
 * the file license.txt that was distributed with this source code.
 */

namespace Tracy;

use Tracy;


class ErrorException extends \ErrorException
{

	public function __construct($message, $code, $severity, $file, $line, \Exception $previous = NULL, $context = NULL)
	{
		parent::__construct($message, $code, $severity, $file, $line, $previous);
		$this->context = $context;
	}

}
