<?php

require __DIR__ . '/../src/tracy.php';

use Tracy\Debugger;

Debugger::enable(Debugger::DETECT, __DIR__ . '/log');


if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) { // AJAX request
	Debugger::barDump('AJAX request');
	$data = [rand(), rand(), rand()];
	header('Content-Type: application/json');
	header('Cache-Control: no-cache');
	echo json_encode($data);
	exit;
}

Debugger::barDump('classic request');

?>
<!DOCTYPE html><html class=arrow><link rel="stylesheet" href="assets/style.css">

<h1>Tracy: AJAX demo</h1>

<button>Click here</button> <span id=result></span>

<script src="https://code.jquery.com/jquery-2.2.2.min.js"></script>
<script>

var jqxhr;

$('button').click(function() {
	$('#result').text('loading…');

	if (jqxhr) {
		jqxhr.abort();
	}

	jqxhr = $.getJSON('?', function(data) {
		$('#result').text('loaded: ' + data);

	}).fail(function() {
		$('#result').text('error');
	});
});


</script>
