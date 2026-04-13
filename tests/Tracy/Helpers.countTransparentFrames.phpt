<?php declare(strict_types=1);

/**
 * Test: Tracy\Helpers::countTransparentFrames()
 *
 * A frame n is transparent when
 *   - its file is missing (called from a PHP-internal callback), or
 *   - its file is inside $paths, or
 *   - its file is synthetic (eval, CLI), or
 *   - the containing function (trace[n+1].function/class) is annotated @tracySkipLocation.
 */

use Tester\Assert;
use Tracy\Debugger;
use Tracy\Helpers;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/fixtures/transparentWrapper.php';


$opaque = ['class' => 'Tester\Assert', 'function' => 'same', 'file' => __FILE__];
$inPath = ['file' => __DIR__ . '/fixtures/transparentWrapper.php'];
$noFile = ['function' => 'array_map'];
$annotated = ['function' => 'Tracy\TestFixtures\annotated', 'file' => __FILE__];
$eval = ['function' => 'anything', 'file' => __FILE__ . "(10) : eval()'d code"];


test('returns 0 when first frame is opaque', function () use ($opaque) {
	Assert::same(0, Helpers::countTransparentFrames([$opaque], []));
});


test('returns count($trace) when every frame is transparent', function () use ($noFile) {
	Assert::same(2, Helpers::countTransparentFrames([$noFile, $noFile], []));
});


test('returns 0 for empty trace', function () {
	Assert::same(0, Helpers::countTransparentFrames([], []));
});


test('skips frames with missing file (called from PHP-internal)', function () use ($noFile, $opaque) {
	Assert::same(1, Helpers::countTransparentFrames([$noFile, $opaque], []));
});


test('skips frames whose file is inside $paths', function () use ($inPath, $opaque) {
	Assert::same(1, Helpers::countTransparentFrames([$inPath, $opaque], [__DIR__ . '/fixtures']));
	Assert::same(0, Helpers::countTransparentFrames([$inPath, $opaque], []));
});


test('skips frames whose containing function (next frame) is @tracySkipLocation', function () use ($opaque, $annotated) {
	// trace[0]=opaque, trace[1]=annotated → opaque is inside annotated's body → skip opaque
	Assert::same(1, Helpers::countTransparentFrames([$opaque, $annotated, $opaque], []));
});


test('skips frames whose file is a synthetic path (eval, CLI)', function () use ($eval, $opaque) {
	Assert::same(1, Helpers::countTransparentFrames([$eval, $opaque], []));
});


test('default $paths reads from Debugger::$transparentPaths', function () use ($inPath, $opaque) {
	$original = Debugger::$transparentPaths;
	Debugger::$transparentPaths = [__DIR__ . '/fixtures'];
	try {
		Assert::same(1, Helpers::countTransparentFrames([$inPath, $opaque]));
	} finally {
		Debugger::$transparentPaths = $original;
	}
});
