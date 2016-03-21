<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Tracy;


/**
 * Session.
 * @internal
 */
class Session
{
	const COOKIE_NAME = 'tracy-session';

	/** @var string|NULL */
	private $id;

	/** @var resource|NULL */
	private $handle;

	/** @var array|NULL */
	private $data;


	public function open($directory)
	{
		$cookie = isset($_COOKIE[self::COOKIE_NAME]) ? $_COOKIE[self::COOKIE_NAME] : NULL;
		if ($directory && is_string($cookie) && preg_match('#^[0-9a-f]{10}\z#i', $cookie)) {
			$this->id = $cookie;
		} elseif ($directory && PHP_SAPI !== 'cli' && !headers_sent()) {
			$this->id = substr(md5(uniqid('', TRUE)), 0, 10);
			setcookie(self::COOKIE_NAME, $this->id, 0, '/', '', FALSE, TRUE);
		} else {
			return;
		}

		$this->handle = fopen($directory . '/tracy.' . $this->id, 'a+');
	}


	public function getId()
	{
		return $this->id;
	}


	public function & getContent()
	{
		if ($this->handle && $this->data === NULL) {
			flock($this->handle, LOCK_EX);
			$this->data = @unserialize(stream_get_contents($this->handle)) ?: []; // @ - file may be empty
		}
		return $this->data;
	}


	public function __destruct()
	{
		if ($this->handle && $this->data !== NULL) {
			ftruncate($this->handle, 0);
			fwrite($this->handle, serialize($this->data));
			fclose($this->handle);
			$this->handle = NULL;
		}
	}

}
