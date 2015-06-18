<?php

// creates tracy.phar
if (!class_exists('Phar') || ini_get('phar.readonly')) {
	echo "Enable Phar extension and set directive 'phar.readonly=off'.\n";
	die(1);
}

@unlink('tracy.phar'); // @ - file may not exist

$phar = new Phar('tracy.phar');
$phar->setStub("<?php
require 'phar://' . __FILE__ . '/tracy.php';
__HALT_COMPILER();
");

$phar->startBuffering();
foreach ($iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/../../src', RecursiveDirectoryIterator::SKIP_DOTS)) as $file) {
	echo "adding: {$iterator->getSubPathname()}\n";
	$s = php_strip_whitespace($file);

	if (in_array($file->getExtension(), array('js', 'css'))) {
		continue;

	} elseif ($file->getExtension() === 'phtml') {
		$s = preg_replace_callback('#<\?php (require |readfile\(|.*file_get_contents\().*?(/.+\.(js|css))\'\)* \?>#', function ($m) use ($file) {
			return file_get_contents($file->getPath() . $m[2]);
		}, $s);
		$s = preg_replace_callback('#(<(script|style).*>)(.*)(</)#Uis', function ($m) {
			list(, $begin, $type, $s, $end) = $m;

			if (strpos($s, '<?php') !== FALSE) {
				return $m[0];

			} elseif ($type === 'script' && function_exists('curl_init')) {
				$curl = curl_init('http://closure-compiler.appspot.com/compile');
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($curl, CURLOPT_POST, 1);
				curl_setopt($curl, CURLOPT_POSTFIELDS, 'output_info=compiled_code&js_code=' . urlencode($s));
				$s = curl_exec($curl);
				curl_close($curl);

			} elseif ($type === 'style') {
				$s = preg_replace('#/\*.*?\*/#s', '', $s); // remove comments
				$s = preg_replace('#[ \t\r\n]+#', ' ', $s); // compress space, ignore hard space
				$s = preg_replace('# ([^0-9a-z.\#*-])#i', '$1', $s);
				$s = preg_replace('#([^0-9a-z%)]) #i', '$1', $s);
				$s = str_replace(';}', '}', $s); // remove leading semicolon
				$s = trim($s);
			}

			return $begin . $s . $end;
		}, $s);
	}

	$phar[$iterator->getSubPathname()] = $s;
}

$phar->stopBuffering();
$phar->compressFiles(Phar::GZ);

echo "OK\n";
