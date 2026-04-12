<?php declare(strict_types=1);

/**
 * @phpVersion 7.4
 */

use Tester\Assert;
use Tracy\Dumper;

require __DIR__ . '/../bootstrap.php';


// reference detection works with typed properties
$test = new class {
	public int $int = 0;
};

$arr = [&$test->int];

Assert::match('array (1)
   0 => &1 0
', Dumper::toText($arr));
