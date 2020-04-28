<?php

declare(strict_types=1);

use Tester\Assert;
use Tracy\Helpers;


require __DIR__ . '/../bootstrap.php';


Assert::same('for(i=0;i<10;i++}', Helpers::minifyJs('for ( i = 0 ; i < 10 ; i ++ }'));
Assert::same('a=10+20', Helpers::minifyJs('a /* comment */ = /* comment */ 10 /* comment */ + /* comment */ 20 /* comment */ '));
Assert::same('a=" a: b; ";', Helpers::minifyJs('a = " a: b; ";'));
