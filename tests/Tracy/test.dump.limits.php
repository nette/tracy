<style>
.dump { color:black; background:white; font-size:12px; text-align:left } .dump span { color: gray }
</style>

<h1>Nette::Debug dump with limits test</h1>


<?php
require_once '../../Nette/loader.php';

/*use Nette::Debug;*/


$arr = array(
	'file' => file_get_contents(__FILE__),

	array(
		array(
			array('hello' => 'world'),
		),
	),

	'file2' => file_get_contents(__FILE__),

	(object) array(
		(object) array(
			(object) array('hello' => 'world'),
		),
	),
);

$arr[] = &$arr;

Debug::dump($arr);


// string(666) "..."
