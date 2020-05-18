<?php

namespace Tracy;


interface IStorage
{

	/**
	 * @return void
	 */
	function initialize();


	/**
	 * @return bool
	 */
	function isActive();


	/**
	 * @param  array        $data
	 * @param  string       $key1
	 * @param  string|null  $key2
	 * @return void
	 */
	function save($data, $key1, $key2 = null);


	/**
	 * @param  string       $key1
	 * @param  string|null  $key2
	 * @return array
	 */
	function load($key1, $key2 = null);
}
