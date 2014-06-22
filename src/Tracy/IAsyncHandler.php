<?php

/**
 * This file is part of the Tracy (http://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 */

namespace Tracy;

use Tracy;


/**
 * Asynchronous request handler.
 *
 * @author     Miloslav Hůla
 */
interface IAsyncHandler
{

	/**
	 * Handles asynchronous call.
	 * @param  mixed
	 * @return mixed
	 */
	function handleAsyncCall($parameters);


	/**
	 * @param  string
	 */
	function setHandlerId($id);

}
