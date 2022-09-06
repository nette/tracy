<?php

declare(strict_types=1);

use Tester\Assert;
use Tracy\Dumper;

require __DIR__ . '/../bootstrap.php';


// will not throw Fatal error: Nesting level too deep - recursive dependency

$a[] = [&$a];

Assert::match(<<<'XX'
array (1)
   0 => array (1)
   |  0 => &1 array (1)
   |  |  0 => array (1)
   |  |  |  0 => &1 array (1) RECURSION
XX
	, Dumper::toText($a));
