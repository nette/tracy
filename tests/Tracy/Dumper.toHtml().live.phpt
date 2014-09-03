<?php

/**
 * Test: Tracy\Dumper::toHtml() live
 */

use Tracy\Dumper,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';


class Test
{
	public $x = array(10, NULL);

	private $y = 'hello';

	protected $z = 30.0;
}


$options = array(Dumper::LIVE => TRUE);

Assert::match( '<pre class="tracy-dump"><span class="tracy-dump-null">NULL</span>
</pre>', Dumper::toHtml(NULL, $options) );

Assert::match( '<pre class="tracy-dump"><span class="tracy-dump-bool">TRUE</span>
</pre>', Dumper::toHtml(TRUE, $options) );

Assert::match( '<pre class="tracy-dump"><span class="tracy-dump-number">0</span>
</pre>', Dumper::toHtml(0, $options) );

Assert::match(
	'<pre class="tracy-dump" data-tracy-dump=\'[[0,null],[1,true],[2,false],[3,0],[4,0],[5,"string"],[6,"\\\\x00"],[7,{"type":"INF"}],[8,{"type":"-INF"}],[9,{"type":"NAN"}]]\'></pre>',
	Dumper::toHtml(array(NULL, TRUE, FALSE, 0, 0.0, 'string', "\x00", INF, -INF, NAN), $options)
);

Assert::match( '<pre class="tracy-dump"><span class="tracy-dump-array">array</span> ()
</pre>', Dumper::toHtml(array(), $options) );
Assert::same( array(), Dumper::fetchLiveData() );


Assert::match(
	'<pre class="tracy-dump" data-tracy-dump=\'{"object":1}\'></pre>',
	Dumper::toHtml(new stdClass, $options)
);

Assert::match(
	'<pre class="tracy-dump" data-tracy-dump=\'{"object":2}\'></pre>',
	Dumper::toHtml(new stdClass, $options) // different object
);
$data = Dumper::fetchLiveData();
Assert::notSame($data[1]['hash'], $data[2]['hash']);
Assert::same( array(
	1 => array('name' => 'stdClass', 'hash' => $data[1]['hash'], 'editor' => NULL, 'items' => array()),
	2 => array('name' => 'stdClass', 'hash' => $data[2]['hash'], 'editor' => NULL, 'items' => array()),
), $data );

Assert::match(
	'<pre class="tracy-dump" data-tracy-dump=\'{"resource":%d%}\'></pre>',
	Dumper::toHtml(fopen(__FILE__, 'r'), $options)
);
Assert::match(
	'{"3":{"name":"stream resource","hash":%d%,"items":[["wrapper_type","plainfile"],%a%]}}',
	json_encode(Dumper::fetchLiveData())
);


Assert::match(
	'<pre class="tracy-dump tracy-collapsed" data-tracy-dump=\'{"object":4}\'></pre>',
	Dumper::toHtml(new Test, $options + array(Dumper::COLLAPSE => TRUE))
);
$data = Dumper::fetchLiveData();
Assert::same( array(
	4 => array(
		'name' => 'Test',
		'hash' => $data[4]['hash'],
		'editor' => NULL,
		'items' => array(
			array('x', array(array(0, 10), array(1, NULL)), 0),
			array('y', 'hello', 2),
			array('z', 30.0, 1),
		),
	),
), $data );


Assert::match(
	'<pre class="tracy-dump" title="Dumper::toHtml(new Test, $options + array(&#039;location&#039; =&gt; Dumper::LOCATION_SOURCE | Dumper::LOCATION_LINK | Dumper::LOCATION_CLASS))
in file %a% on line %d%" data-tracy-href="editor://open/?file=%a%&amp;line=%d%" data-tracy-dump=\'{"object":5}\'><small>in <a href="editor://open/?file=%a%&amp;line=%d%" title="%a%:%d%">%a%:%d%</a></small></pre>',
	Dumper::toHtml(new Test, $options + array('location' => Dumper::LOCATION_SOURCE | Dumper::LOCATION_LINK | Dumper::LOCATION_CLASS))
);

Assert::match(
	'{"5":{"name":"Test","hash":"%h%","editor":{"file":"%a%","line":%d%,"url":"editor:%a%"},"items":[["x",[[0,10],[1,null]],0],["y","hello",2],["z",30,1]]}}',
	json_encode(Dumper::fetchLiveData())
);
