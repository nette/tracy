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

$xpath = new DomXPath($dom);
$nodeList = $xpath->query('//li');
$namedNodeMap = $nodeList->item(0)->attributes;
$element = $nodeList->item(0);


Assert::match(<<<'XX'
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
   |  internalSubset: null
   |  lastChild: null
   |  localName: null
   |  name: 'html'
   |  namespaceURI: null
   |  nextSibling: DOMElement #%d% see below
   |  nodeName: 'html'
   |  nodeType: 10
   |  nodeValue: null
   |  notations: DOMNamedNodeMap #%d% ...
   |  ownerDocument: DOMDocument #%d% RECURSION
   |  parentNode: DOMDocument #%d% RECURSION
   |  prefix: ''
   |  previousSibling: null
   |  publicId: ''
   |  systemId: ''
   |  textContent: ''
   documentElement: DOMElement #%d%
   |  attributes: DOMNamedNodeMap #%d% ...
%A%
   localName: null
   namespaceURI: null
   nextSibling: null
   nodeName: '#document'
   nodeType: 13
   nodeValue: null
   ownerDocument: null
   parentNode: null
   prefix: ''
   preserveWhiteSpace: true
   previousSibling: null
   recover: false
   resolveExternals: false
   standalone: true
   strictErrorChecking: true
   substituteEntities: false
   textContent: 'Ahoj'
   validateOnParse: false
   version: null
   xmlEncoding: null
   xmlStandalone: true
   xmlVersion: null
XX
, Dumper::toText($dom, [Dumper::DEPTH => 2]));


Assert::match(<<<'XX'
DOMNodeList #%d%
   length: 1
   items: array (1)
   |  0 => DOMElement #%d% ...
XX
, Dumper::toText($nodeList, [Dumper::DEPTH => 2]));


Assert::match(<<<'XX'
DOMNamedNodeMap #%d%
   length: 1
   items: array (1)
   |  'class' => DOMAttr #%d%
   |  |  attributes: null
   |  |  baseURI: null
   |  |  childNodes: DOMNodeList #%d% ...
   |  |  firstChild: DOMText #%d% ...
   |  |  lastChild: DOMText #%d% ...
   |  |  localName: 'class'
   |  |  name: 'class'
   |  |  namespaceURI: null
   |  |  nextSibling: null
   |  |  nodeName: 'class'
   |  |  nodeType: 2
   |  |  nodeValue: 'a'
   |  |  ownerDocument: DOMDocument #%d% ...
   |  |  ownerElement: DOMElement #%d% ...
   |  |  parentNode: DOMElement #%d% ...
   |  |  prefix: ''
   |  |  previousSibling: null
   |  |  schemaTypeInfo: null
   |  |  specified: true
   |  |  textContent: 'a'
   |  |  value: 'a'
XX
, Dumper::toText($namedNodeMap, [Dumper::DEPTH => 3]));


Assert::match(<<<'XX'
DOMElement #%d%
   attributes: DOMNamedNodeMap #%d% ...
   baseURI: null
%A%
   previousSibling: null
   schemaTypeInfo: null
   tagName: 'li'
   textContent: 'Ahoj'
XX
, Dumper::toText($element, [Dumper::DEPTH => 1]));


Assert::match(<<<'XX'
DOMXPath #%d%%A%
XX
, Dumper::toText($xpath, [Dumper::DEPTH => 1]));
