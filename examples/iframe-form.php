<?php declare(strict_types=1);

require __DIR__ . '/../src/tracy.php';

use Tracy\Debugger;

// For security reasons, Tracy is visible only on localhost.
// You may force Tracy to run in development mode by passing the Debugger::Development instead of Debugger::Detect.
Debugger::enable(Debugger::Detect, __DIR__ . '/log');


// Form posted into a hidden iframe – Tracy starts a new bar instance inside
// the iframe, so the request is effectively invisible (issue nette/tracy#427).
if (!empty($_POST['data'])) {
	bdump($_POST['data'], 'received in hidden iframe');
	echo '<script>parent.setStatus(' . json_encode($_POST['data']) . ' + " received!")</script>';
	exit;
}

?>
<!DOCTYPE html><html class=arrow><link rel="stylesheet" href="assets/style.css">

<h1>Tracy: hidden iframe form demo (issue #427)</h1>

<p>Submitting the form posts into a hidden iframe. The request <em>is</em> handled
	by Tracy, but the bar is rendered inside the invisible iframe, so it is not
	shown in the parent page.</p>

<iframe name="hiddenIframe"></iframe>

<form target="hiddenIframe" action="iframe-form.php" method="post">
	<input type="text" name="data" value="hello">
	<input type="submit">
</form>

<h2 id="status">Waiting for submit...</h2>

<script>
	function setStatus(text) {
		document.getElementById('status').innerText = text;
	}
</script>

<?php
if (Debugger::$productionMode) {
	echo '<p><b>For security reasons, Tracy is visible only on localhost. Look into the source code to see how to enable Tracy.</b></p>';
}
