<?php

declare(strict_types=1);

use Tester\Assert;
use Tracy\BlueScreen\Action;

require __DIR__ . '/../bootstrap.php';


$blueScreen = new Tracy\BlueScreen;

// search
Assert::with($blueScreen, function () {
	Assert::equal(
		[
			new Action(
				'search',
				'https://www.google.com/search?sourceid=tracy&q=Exception+',
				true
			),
		],
		$this->renderActions(new Exception)
	);

	Assert::equal(
		[
			new Action(
				'search',
				'https://www.google.com/search?sourceid=tracy&q=Exception+The+%3D+message',
				true
			),
		],
		$this->renderActions(new Exception('The = message', 123))
	);

	Assert::equal(
		[
			new Action(
				'search',
				'https://www.google.com/search?sourceid=tracy&q=Message',
				true
			),
		],
		$this->renderActions(new ErrorException('Message', 123, E_USER_WARNING))
	);
});


// skip error
Assert::with($blueScreen, function () {
	$e = new ErrorException;
	$_SERVER['REQUEST_URI'] = '/';
	$_SERVER['HTTP_HOST'] = 'localhost';
	$search = new Action(
		'search',
		'https://www.google.com/search?sourceid=tracy&q=',
		true
	);

	Assert::equal(
		[$search],
		$this->renderActions($e)
	);

	if (PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg') {
		$e->skippable = true;
		Assert::equal(
			[
				$search,
				new Action(
					'skip error',
					'http://localhost/?_tracy_skip_error'
				),
			],
			$this->renderActions($e)
		);
	}
});


// action 'open file'
Assert::with($blueScreen, function () {
	Assert::equal(
		new Action(
			'open file',
			'editor://open/?file=' . urlencode(__FILE__) . '&line=1&search=&replace='
		),
		$this->renderActions(new Exception(" '" . __FILE__ . "'"))[0]
	);

	Assert::equal(
		new Action(
			'open file',
			'editor://open/?file=' . urlencode(__FILE__) . '&line=1&search=&replace='
		),
		$this->renderActions(new Exception(' "' . __FILE__ . '"'))[0]
	);

	$ds = urlencode(DIRECTORY_SEPARATOR);
	Assert::equal(
		new Action(
			'create file',
			'editor://create/?file=' . $ds . 'notexists.txt&line=1&search=&replace='
		),
		$this->renderActions(new Exception(' "/notexists.txt"'))[0]
	);

	Assert::equal(
		new Action(
			'create file',
			'editor://create/?file=c%3A%5Cnotexists.txt&line=1&search=&replace='
		),
		$this->renderActions(new Exception(' "c:\notexists.txt"'))[0]
	);

	Assert::equal(
		new Action(
			'create file',
			'editor://create/?file=c%3A' . $ds . 'notexists.txt&line=1&search=&replace='
		),
		$this->renderActions(new Exception(' "c:/notexists.txt"'))[0]
	);

	Assert::count(1, $this->renderActions(new Exception(' "/notfile"')));
	Assert::count(1, $this->renderActions(new Exception(' "notfile"')));
});


// $e->tracyAction
Assert::with($blueScreen, function () {
	$e = new Exception;
	$e->tracyAction = [];
	Assert::count(1, $this->renderActions($e));

	$e = new Exception;
	$e->tracyAction = ['label' => 'b', 'link' => 'a'];
	Assert::equal(
		new Action('b', 'a'),
		$this->renderActions($e)[0]
	);
});


// addAction
$blueScreen->addAction(function (Exception $e) {
	return [];
});

$blueScreen->addAction(function (Exception $e) {
	return ['label' => 'a', 'link' => 'b'];
});

Assert::with($blueScreen, function () {
	$e = new Exception;
	Assert::equal(
		[
			new Action('a', 'b'),
			new Action(
				'search',
				'https://www.google.com/search?sourceid=tracy&q=Exception+',
				true
			),
		],
		$this->renderActions($e)
	);
});



// isset() error
class FooException extends Exception
{
	public function __isset($name)
	{
		throw new Exception('Isset is disabled');
	}
}

Assert::with($blueScreen, function () {
	Assert::count(2, $this->renderActions(new FooException));
});
