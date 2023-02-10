<?php

/**
 * @phpVersion 8.2
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Dumper;

require __DIR__ . '/../bootstrap.php';


function sensitiveParameters(
	#[SensitiveParameter]
	string $secret,
	string $normal
) {
	throw new Exception;
}


try {
	sensitiveParameters('password', 'normal');
} catch (Throwable $e) {
}


$expect = <<<'XX'
Exception #%d%
   message: ''
   string: ''
   code: 0
   file: '%a%'
   line: %d%
   trace: array (1)
   |  0 => array (4)
   |  |  'file' => '%a%'
   |  |  'line' => %d%
   |  |  'function' => 'sensitiveParameters'
   |  |  'args' => array (2)
   |  |  |  0 => ***** (string)
   |  |  |  1 => 'normal'
   previous: null
XX;

Assert::match($expect, Dumper::toText($e));
