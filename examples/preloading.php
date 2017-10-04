<?php

require __DIR__ . '/../src/tracy.php';

use Tracy\Debugger;

// session is required for this functionality
session_start();

// For security reasons, Tracy is visible only on localhost.
// You may force Tracy to run in development mode by passing the Debugger::DEVELOPMENT instead of Debugger::DETECT.
Debugger::enable(Debugger::DETECT, __DIR__ . '/log');


if (isset($_GET['sleep'])) {
	header('Content-Type: application/javascript');
	sleep(10);
	exit;
}

?>
<!DOCTYPE html><html class=arrow><link rel="stylesheet" href="assets/style.css">

<h1>Tracy: Preloading</h1>

<?php Debugger::getBar()->renderLoader() ?>

<script src="?sleep=1"></script>


<?php

if (Debugger::$productionMode) {
	echo '<p><b>For security reasons, Tracy is visible only on localhost. Look into the source code to see how to enable Tracy.</b></p>';
}
