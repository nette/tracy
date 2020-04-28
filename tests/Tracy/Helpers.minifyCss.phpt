<?php

declare(strict_types=1);

use Tester\Assert;
use Tracy\Helpers;


require __DIR__ . '/../bootstrap.php';


Assert::same('a b .c * #d :not(a) b [id=x] [id=x]{}', Helpers::minifyCss('a b .c * #d :not(a) b [id=x] [id=x] {}'));
Assert::same('a{margin: 10px 20% .3 0}', Helpers::minifyCss('a { margin: 10px 20% .3 0; }'));
Assert::same('a{color : blue}', Helpers::minifyCss('a /* comment */ { /* comment */ color /* comment */ : /* comment */ blue /* comment */ }'));
Assert::same('a{content:" a: b; "}', Helpers::minifyCss('a { content: " a: b; "; }'));
