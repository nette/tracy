<?php

/**
 * Test: Tracy\Helpers::getSuggestion()
 */

use Tracy\Helpers;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


Assert::same(NULL, Helpers::getSuggestion([], ''));
Assert::same(NULL, Helpers::getSuggestion([], 'a'));
Assert::same(NULL, Helpers::getSuggestion(['a'], 'a'));
Assert::same('a', Helpers::getSuggestion(['a', 'b'], ''));
Assert::same('b', Helpers::getSuggestion(['a', 'b'], 'a')); // ignore 100% match
Assert::same('a1', Helpers::getSuggestion(['a1', 'a2'], 'a')); // take first
Assert::same(NULL, Helpers::getSuggestion(['aaa', 'bbb'], 'a'));
Assert::same(NULL, Helpers::getSuggestion(['aaa', 'bbb'], 'ab'));
Assert::same(NULL, Helpers::getSuggestion(['aaa', 'bbb'], 'abc'));
Assert::same('bar', Helpers::getSuggestion(['foo', 'bar', 'baz'], 'baz'));
Assert::same('abcd', Helpers::getSuggestion(['abcd'], 'acbd'));
Assert::same('abcd', Helpers::getSuggestion(['abcd'], 'axbd'));
Assert::same(NULL, Helpers::getSuggestion(['abcd'], 'axyd'));


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
