<?php

declare(strict_types=1);

#[\AllowDynamicProperties]
class Test74
{
	public int $x = 1;
	private int $y;
	protected int $z;
}

class Child74 extends Test74
{
	public int $x = 2;
	private int $y;
	protected int $z;

	public $unset1;
	public int $unset2 = 1;
}
