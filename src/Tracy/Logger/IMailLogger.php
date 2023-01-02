<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tracy;


/**
 * Mail Logger.
 */
interface IMailLogger
{
    /**
     * @param callable $mailer
     */
	function setMailer(callable $mailer): void;
}
