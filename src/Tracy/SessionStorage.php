<?php

namespace Tracy;


class SessionStorage implements IStorage
{

	/**
	 * @return void
	 */
	public function initialize()
	{
		ini_set('session.use_cookies', '1');
		ini_set('session.use_only_cookies', '1');
		ini_set('session.use_trans_sid', '0');
		ini_set('session.cookie_path', '/');
		ini_set('session.cookie_httponly', '1');
		session_start();
	}


	/**
	 * @return bool
	 */
	public function isActive(): bool
	{
		return session_status() === PHP_SESSION_ACTIVE;
	}


	/**
	 * @param  array        $data
	 * @param  string       $key1
	 * @param  string|null  $key2
	 * @return void
	 */
	public function save($data, $key1, $key2 = null): void
	{
		if ($key2 !== null) {
			$_SESSION['_tracy'][$key1][$key2] = $data;
		} else {
			$_SESSION['_tracy'][$key1] = $data;
		}
	}


	/**
	 * @param  string       $key1
	 * @param  string|null  $key2
	 * @return array
	 */
	public function load($key1, $key2 = null): array
	{
		if ($key2 !== null) {
			return $_SESSION['_tracy'][$key1][$key2] ?? [];
		} else {
			return $_SESSION['_tracy'][$key1] ?? [];
		}
	}
}
