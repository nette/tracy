<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2017 Miroslav Babjak
 */

namespace Tracy;


interface ILoggerHandler
{
	/**
	 * Method is called after Tracy log exception/message and send email(if set)
	 * @param  string|\Exception|\Throwable
	 * @param  int   one of constant ILogger::INFO, WARNING, ERROR (sends email), EXCEPTION (sends email), CRITICAL (sends email)
	 */
	public function __invoke($message, $priority);

}
