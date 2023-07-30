<?php

/**
 * Test: Tracy\Dumper::toText() & DOM
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Dumper;

require __DIR__ . '/../bootstrap.php';


$dom = new DOMDocument;
$dom->loadHtml('<!doctype html><ul><li class="a">Ahoj</ul>');

$xpath = new DOMXPath($dom);
$nodeList = $xpath->query('//li');
$namedNodeMap = $nodeList->item(0)->attributes;
$element = $nodeList->item(0);


Assert::match(
	<<<'XX'
		DOMDocument #%d%
		   actualEncoding: null
		   attributes: null
		   baseURI: null
		%A%
		   doctype: DOMDocumentType #%d%
		   |  attributes: null
		   |  baseURI: null
		   |  childNodes: %a%
		   |  entities: DOMNamedNodeMap #%d% ...
		   |  firstChild: null
		%A%
		   localName: null
		   namespaceURI: null
		   nextSibling: null
		   nodeName: '#document'
		   nodeType: 13
		   nodeValue: null
		   ownerDocument: null
		%A%
		XX,
	Dumper::toText($dom, [Dumper::DEPTH => 2]),
);


Assert::match(
	<<<'XX'
		DOMNodeList #%d%
		   length: 1
		   items: array (1)
		   |  0 => DOMElement #%d% ...
		XX,
	Dumper::toText($nodeList, [Dumper::DEPTH => 2]),
);


Assert::match(
	<<<'XX'
		DOMNamedNodeMap #%d%
		   length: 1
		   items: array (1)
		   |  'class' => DOMAttr #%d%
		   |  |  attributes: null
		%A%
		XX,
	Dumper::toText($namedNodeMap, [Dumper::DEPTH => 3]),
);


Assert::match(
	<<<'XX'
		DOMElement #%d%
		   attributes: DOMNamedNodeMap #%d% ...
		   baseURI: null
		%A%
		   previousSibling: null
		   schemaTypeInfo: null
		   tagName: 'li'
		   textContent: 'Ahoj'
		XX,
	Dumper::toText($element, [Dumper::DEPTH => 1]),
);


Assert::match(
	<<<'XX'
		DOMXPath #%d%%A%
		XX,
	Dumper::toText($xpath, [Dumper::DEPTH => 1]),
);
