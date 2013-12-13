<?php

// creates tracy.phar
if (!class_exists('Phar') || ini_get('phar.readonly')) {
	echo "Enable Phar extension and set directive 'phar.readonly=off'.\n";
	die(1);
}

@unlink('tracy.phar'); // @ - file may not exist

$p = new Phar('tracy.phar');
$p->setStub("<?php
require 'phar://' . __FILE__ . '/tracy.php';
__HALT_COMPILER();
");

$p->startBuffering();
foreach ($iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/../../src', FilesystemIterator::SKIP_DOTS)) as $file) {
	echo "adding: {$iterator->getSubPathname()}\n";
	$p[$iterator->getSubPathname()] = php_strip_whitespace($file);
}

$p->stopBuffering();
$p->compressFiles(Phar::GZ);

echo "OK\n";
