<?php

/**
 * Test: Tracy\Helpers::getSuggestion()
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Helpers;

require __DIR__ . '/../bootstrap.php';


Assert::same(null, Helpers::getSuggestion([], ''));
Assert::same(null, Helpers::getSuggestion([], 'a'));
Assert::same(null, Helpers::getSuggestion(['a'], 'a'));
Assert::same('a', Helpers::getSuggestion(['a', 'b'], ''));
Assert::same('b', Helpers::getSuggestion(['a', 'b'], 'a')); // ignore 100% match
Assert::same('a1', Helpers::getSuggestion(['a1', 'a2'], 'a')); // take first
Assert::same(null, Helpers::getSuggestion(['aaa', 'bbb'], 'a'));
Assert::same(null, Helpers::getSuggestion(['aaa', 'bbb'], 'ab'));
Assert::same(null, Helpers::getSuggestion(['aaa', 'bbb'], 'abc'));
Assert::same('bar', Helpers::getSuggestion(['foo', 'bar', 'baz'], 'baz'));
Assert::same('abcd', Helpers::getSuggestion(['abcd'], 'acbd'));
Assert::same('abcd', Helpers::getSuggestion(['abcd'], 'axbd'));
Assert::same(null, Helpers::getSuggestion(['abcd'], 'axyd'));


/*
length  allowed ins/del  replacements
-------------------------------------
0       1                0
1       1                1
2       1                1
3       1                1
4       2                1
5       2                2
6       2                2
7       2                2
8       3                2
*/
