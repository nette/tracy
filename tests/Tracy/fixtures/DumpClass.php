<?php

declare(strict_types=1);

#[\AllowDynamicProperties]
class Test
{
	public static $pubs = 1;
	protected static $pros = 2;
	private static $pris = 3;

	public $x = [10, null];
	private $y = 'hello';
	protected $z = 30.0;
}

class Child extends Test
{
	public $x = 1;
	private $y = 2;
	protected $z = 3;

	public $x2 = 4;
	protected $y2 = 5;
	private $z2 = 6;
}

class GrandChild extends Child
{
}
