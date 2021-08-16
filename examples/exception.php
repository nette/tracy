<?php

declare(strict_types=1);

require __DIR__ . '/../src/tracy.php';

use Tracy\Debugger;

// For security reasons, Tracy is visible only on localhost.
// You may force Tracy to run in development mode by passing the Debugger::DEVELOPMENT instead of Debugger::DETECT.
Debugger::enable(Debugger::DETECT, __DIR__ . '/log');

?>
<!DOCTYPE html><link rel="stylesheet" href="assets/style.css">

<h1>Tracy: exception demo</h1>

<?php

class DemoClass
{
	public function first($arg1, $arg2)
	{
		$this->second(true, false);
	}


	public function second($arg1, $arg2)
	{
		self::third([1, 2, 3]);
	}


	public static function third($arg1)
	{
		throw new Exception('The my exception', 123);
	}
}


$a = new class extends \RuntimeException {
	public function setPrevious(\Throwable $e): void
	{
		$ref = new \ReflectionClass($this);
		$parent = $ref->getParentClass()->getParentClass();
		$previous = $parent->getProperty('previous');
		$previous->setAccessible(true);
		$previous->setValue($this, $e);
	}
};
$a->setPrevious($a);

// this line will kill your BlueScreen:
throw $a;