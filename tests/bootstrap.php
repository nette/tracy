<?php

/**
 * Test initialization and helpers.
 *
 * @author     David Grudl
 * @package    Nette\Test
 */


if (@!include __DIR__ . '/../vendor/autoload.php') {
	echo 'Install Nette Tester using `composer update --dev`';
	exit(1);
}


// configure environment
Tester\Helpers::setup();
date_default_timezone_set('Europe/Prague');
$_GET = $_POST = $_COOKIE = array();


// create temporary directory
define('TEMP_DIR', __DIR__ . '/tmp/' . getmypid());
@mkdir(dirname(TEMP_DIR)); // @ - directory may already exist
Tester\Helpers::purge(TEMP_DIR);


if (extension_loaded('xdebug')) {
	xdebug_disable();
	Tester\CodeCoverage\Collector::start(__DIR__ . '/coverage.dat');
}


function test(\Closure $function)
{
	$function();
}
