<?php

use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$blueScreen = new Tracy\BlueScreen;

// search
Assert::with($blueScreen, function () {
	Assert::same(
		[
			[
				'link' => 'https://www.google.com/search?sourceid=tracy&q=Exception+',
				'label' => 'search',
				'external' => true,
			],
		],
		$this->renderActions(new Exception)
	);

	Assert::same(
		[
			[
				'link' => 'https://www.google.com/search?sourceid=tracy&q=Exception+The+%3D+message',
				'label' => 'search',
				'external' => true,
			],
		],
		$this->renderActions(new Exception('The = message', 123))
	);

	Assert::same(
		[
			[
				'link' => 'https://www.google.com/search?sourceid=tracy&q=Message',
				'label' => 'search',
				'external' => true,
			],
		],
		$this->renderActions(new ErrorException('Message', 123, E_USER_WARNING))
	);
});


// skip error
Assert::with($blueScreen, function () {
	$e = new ErrorException;
	$_SERVER['REQUEST_URI'] = '/';
	$_SERVER['HTTP_HOST'] = 'localhost';
	$search = [
		'link' => 'https://www.google.com/search?sourceid=tracy&q=',
		'label' => 'search',
		'external' => true,
	];

	Assert::same(
		[$search],
		$this->renderActions($e)
	);

	$e->skippable = true;
	Assert::same(
		[
			$search,
			[
				'link' => 'http://localhost/?_tracy_skip_error',
				'label' => 'skip error',
			],
		],
		$this->renderActions($e)
	);
});
