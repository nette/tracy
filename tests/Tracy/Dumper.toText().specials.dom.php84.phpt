<?php

/**
 * Test: Tracy\Dumper::toText() & Dom
 * @phpversion 8.4
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Dumper;

require __DIR__ . '/../bootstrap.php';


$dom = Dom\HTMLDocument::createFromString('<!doctype html><ul><li class="a">Ahoj</ul>', Dom\HTML_NO_DEFAULT_NS);
$xpath = new Dom\XPath($dom);
$nodeList = $xpath->query('//li');
$namedNodeMap = $nodeList->item(0)->attributes;
$collection = $dom->getElementsByTagName('li');
$element = $nodeList->item(0);


Assert::match(
	<<<'XX'
		Dom\HTMLDocument #%d%
		   URL: 'about:blank'
		   baseURI: 'about:blank'
		%A%
		   doctype: Dom\DocumentType #%d%
		   |  attributes: null
		   |  baseURI: 'about:blank'
		   |  childNodes: %a%
		   |  entities: Dom\DtdNamedNodeMap #%d% ...
		   |  firstChild: null
		%A%
		   documentElement: Dom\Element #%d%
		%A%
		XX,
	Dumper::toText($dom, [Dumper::DEPTH => 2]),
);


Assert::match(
	<<<'XX'
		Dom\NodeList #%d%
		   length: 1
		   items: array (1)
		   |  0 => Dom\Element #%d% ...
		XX,
	Dumper::toText($nodeList, [Dumper::DEPTH => 2]),
);


Assert::match(
	<<<'XX'
		Dom\NamedNodeMap #%d%
		   length: 1
		   items: array (1)
		   |  'class' => Dom\Attr #%d%
		   |  |  attributes: null
		%A%
		XX,
	Dumper::toText($namedNodeMap, [Dumper::DEPTH => 3]),
);


Assert::match(
	<<<'XX'
		Dom\HTMLCollection #%d%
		   length: 1
		   items: array (1)
		   |  'li' => Dom\Element #%d% ...
		XX,
	Dumper::toText($collection, [Dumper::DEPTH => 2]),
);


Assert::match(
	<<<'XX'
		Dom\Element #%d%
		   attributes: Dom\NamedNodeMap #%d% ...
		   baseURI: 'about:blank'
		%A%
		   previousSibling: null
		   substitutedNodeValue: 'Ahoj'
		   tagName: 'li'
		   textContent: 'Ahoj'
		XX,
	Dumper::toText($element, [Dumper::DEPTH => 1]),
);


Assert::match(
	<<<'XX'
		Dom\TokenList #%d%
		   length: 1
		   items: array (1)
		   |  0 => 'a'
		XX,
	Dumper::toText($element->classList, [Dumper::DEPTH => 2]),
);


Assert::match(
	<<<'XX'
		Dom\XPath #%d%%A%
		XX,
	Dumper::toText($xpath, [Dumper::DEPTH => 1]),
);
