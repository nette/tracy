<?php

// creates tracy.phar
if (!class_exists('Phar') || ini_get('phar.readonly')) {
	echo "Enable Phar extension and set directive 'phar.readonly=off'";
	die(1);
}

unlink('tracy.phar');

$p = new Phar('tracy.phar');
$p->setStub('<?php
require "src/tracy.php";
__halt_compiler();
');

$p->startBuffering();
foreach ($iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/../../src')) as $file) {
	echo "adding: {$iterator->getSubPathname()}\n";
	$p[$iterator->getSubPathname()] = php_strip_whitespace($file);
}

$p->stopBuffering();
$p->compressFiles(Phar::GZ);

echo 'OK';
