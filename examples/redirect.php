<?php

require __DIR__ . '/../src/tracy.php';

use Tracy\Debugger;

// session is required for this functionality
session_start();

// For security reasons, Tracy is visible only on localhost.
// You may force Tracy to run in development mode by passing the Debugger::DEVELOPMENT instead of Debugger::DETECT.
Debugger::enable(Debugger::DETECT, __DIR__ . '/log');


if (empty($_GET['redirect'])) {
	Debugger::barDump('before redirect');
	header('Location: ' . $_SERVER['REQUEST_URI'] . '?&redirect=1');
	exit;
}

Debugger::barDump('after redirect');

?>
<!DOCTYPE html><html class=arrow><link rel="stylesheet" href="assets/style.css">

<h1>Tracy: redirect demo</h1>


<?php
if (Debugger::$productionMode) {
	echo '<p><b>For security reasons, Tracy is visible only on localhost. Look into the source code to see how to enable Tracy.</b></p>';
}
