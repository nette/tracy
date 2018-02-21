<?php

class Test
{
	public $x = [10, null];

	private $y = 'hello';

	protected $z = 30.0;
}

class TestDebugInfo extends Test
{
	public $a = 20;

	protected $c = "hidden";

	private $d = "visible";

	public function __debugInfo() {
		$vars = get_object_vars($this);
		unset($vars['c']);
		$vars['b'] = 'virtual';
		return $vars;
	}
}
