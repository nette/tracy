<?php

require __DIR__ . '/../src/tracy.php';

use Tracy\Debugger;

Debugger::enable(Debugger::DEVELOPMENT, __DIR__ . '/log');

?>
<!DOCTYPE html><html class=arrow><link rel="stylesheet" href="assets/style.css">

<h1>Tracy: bar dump demo</h1>

<p>You can dump variables to bar in rightmost bottom egde.</p>

<?php
$arr = array(10, 20.2, TRUE, NULL, 'hello', (object) NULL, array());

Debugger::barDump(get_defined_vars());

Debugger::barDump($arr, 'The Array');

Debugger::barDump('<a href="#">test</a>', 'String');
