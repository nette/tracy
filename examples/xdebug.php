<?php

declare(strict_types=1);

require __DIR__ . '/../src/tracy.php';

use Tracy\Debugger;

// For security reasons, Tracy is visible only on localhost.
// You may force Tracy to run in development mode by passing the Debugger::Development instead of Debugger::Detect.
//Debugger::$strictMode = true;
Debugger::enable(Debugger::Detect, __DIR__ . '/log');

?>
<!DOCTYPE html><link rel="stylesheet" href="assets/style.css">

<h1>Tracy: exception demo</h1>

<?php

class DemoClass
{
	public function first($arg1, $arg2)
	{
		$arg1 = 'new';
		$arg3 = 'xxx';
		$this->second();
	}


	public function second()
	{
		self::third([1, 2, 3]);
	}


	public static function third($arg5)
	{
		//require __DIR__ . '/assets/E_COMPILE_WARNING-1.php';
		//require __DIR__ . '/assets/E_COMPILE_ERROR.php';
//		trigger_error('jo', E_USER_ERROR);
//		dump(new Exception);
//		dumpe(xdebug_get_function_stack( [ 'local_vars' => true, 'params_as_values' => true ] ));
		try {
			throw new Exception('Original');
		} catch (Exception $e) {
			throw new Exception('The my exception', 123, $e);
		}
		$a++;
	}
}



function demo($a, $b)
{
	$demo = new DemoClass;
	$demo->first($a, $b);
}


if (Debugger::$productionMode) {
	echo '<p><b>For security reasons, Tracy is visible only on localhost. Look into the source code to see how to enable Tracy.</b></p>';
}

demo(10, 'any string');
