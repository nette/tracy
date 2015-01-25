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
	'<pre class="tracy-dump" data-tracy-dump=\'{"object":"01"}\'></pre>',
	Dumper::toHtml(new stdClass, $options)
);

Assert::match(
	'<pre class="tracy-dump" data-tracy-dump=\'{"object":"02"}\'></pre>',
	Dumper::toHtml(new stdClass, $options) // different object
);
Assert::same( array(
	'01' => array('name' => 'stdClass', 'editor' => NULL, 'items' => array()),
	'02' => array('name' => 'stdClass', 'editor' => NULL, 'items' => array()),
), Dumper::fetchLiveData() );


Assert::match(
	'<pre class="tracy-dump" data-tracy-dump=\'{"resource":%d%}\'></pre>',
	Dumper::toHtml(fopen(__FILE__, 'r'), $options)
);
Assert::count( 1, Dumper::fetchLiveData() );


Assert::match(
	'<pre class="tracy-dump tracy-collapsed" data-tracy-dump=\'{"object":"03"}\'></pre>',
	Dumper::toHtml(new Test, $options + array(Dumper::COLLAPSE => TRUE))
);
Assert::same( array(
	'03' => array(
		'name' => 'Test',
		'editor' => NULL,
		'items' => array(
			array('x', array(array(0, 10), array(1, NULL)), 0),
			array('y', 'hello', 2),
			array('z', 30.0, 1),
		),
	),
), Dumper::fetchLiveData() );


Assert::match(
	'<pre class="tracy-dump" title="Dumper::toHtml(new Test, $options + array(&#039;location&#039; =&gt; Dumper::LOCATION_SOURCE | Dumper::LOCATION_LINK | Dumper::LOCATION_CLASS))
in file %a% on line %d%" data-tracy-href="editor://open/?file=%a%&amp;line=%d%" data-tracy-dump=\'{"object":"04"}\'><small>in <a href="editor://open/?file=%a%&amp;line=%d%" title="%a%:%d%">%a%:%d%</a></small></pre>',
	Dumper::toHtml(new Test, $options + array('location' => Dumper::LOCATION_SOURCE | Dumper::LOCATION_LINK | Dumper::LOCATION_CLASS))
);

$data = Dumper::fetchLiveData();
Assert::type( 'int', $data['04']['editor']['line'] );
Assert::type( 'string', $data['04']['editor']['url'] );
unset($data['04']['editor']['line'], $data['04']['editor']['url']);

Assert::same( array(
	'04' => array(
		'name' => 'Test',
		'editor' => array(
			'file' => __FILE__,
		),
		'items' => array(
			array('x', array(array(0, 10), array(1, NULL)), 0),
			array('y', 'hello', 2),
			array('z', 30.0, 1),
		),
	),
), $data );
