<?php declare(strict_types=1);

/**
 * Test: Tracy\Bar lazy panels
 */

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

if (PHP_SAPI === 'cli') {
	Tester\Environment::skip('Requires CGI mode');
}


class LazyPanel implements Tracy\IBarPanel
{
	public int $tabCalls = 0;
	public int $panelCalls = 0;


	public function getTab(): string
	{
		$this->tabCalls++;
		return 'lazy tab';
	}


	public function getPanel(): string
	{
		$this->panelCalls++;
		return '<h1>lazy content</h1>';
	}
}


class LazyPanelSessionStorage implements Tracy\SessionStorage
{
	public array $data = [];


	public function isAvailable(): bool
	{
		return true;
	}


	public function &getData(): array
	{
		return $this->data;
	}
}


function createLazyPanelDefer(LazyPanelSessionStorage $storage): Tracy\DeferredContent
{
	$defer = new Tracy\DeferredContent($storage);
	$defer->sendAssets();
	return $defer;
}


test('lazy panel token survives an AJAX response', function () {
	$_SERVER['HTTP_X_TRACY_AJAX'] = 'abcdef1234';
	setHtmlMode();
	$storage = new LazyPanelSessionStorage;
	$defer = createLazyPanelDefer($storage);
	$panel = new LazyPanel;
	$bar = new Tracy\Bar;
	$bar->addPanel($panel, 'test.panel', lazy: true);

	$bar->render($defer);
	$bar->renderLazyPanels($defer);

	Assert::same(1, $panel->tabCalls);
	Assert::same(1, $panel->panelCalls);
	Assert::match('<h1>lazy content</h1>%A%tracy-icons%A%', $storage->data['lazy-panels']['abcdef1234']['panels']['test-panel']);

	Assert::same(1, preg_match('#^Tracy\.Debug\.loadAjax\((.*)\);\n$#s', $storage->data['setup']['abcdef1234']['code'], $match));
	$content = json_decode($match[1], true, flags: JSON_THROW_ON_ERROR);
	Assert::match('%A%data-tracy-lazy="abcdef1234.test-panel"%A%', $content['panels']);

	unset($_SERVER['HTTP_X_TRACY_AJAX']);
});


test('lazy panel token survives a redirect', function () {
	setHtmlMode();
	header('Location: /next');
	$storage = new LazyPanelSessionStorage;
	$defer = createLazyPanelDefer($storage);
	$panel = new LazyPanel;
	$bar = new Tracy\Bar;
	$bar->addPanel($panel, 'test.panel', lazy: true);

	$bar->render($defer);
	$bar->renderLazyPanels($defer);

	$token = $defer->getRequestId() . '.test-panel';
	Assert::same(1, $panel->tabCalls);
	Assert::same(1, $panel->panelCalls);
	Assert::hasKey('test-panel', $storage->data['lazy-panels'][$defer->getRequestId()]['panels']);
	Assert::match('%A%data-tracy-lazy="' . $token . '"%A%', $storage->data['redirect'][0]['content']['panels']);

	header_remove('Location');
	http_response_code(200);
});


test('lazy panel content is consumed once', function () {
	setHtmlMode();
	$storage = new LazyPanelSessionStorage;
	$storage->data['lazy-panels']['abcdef1234'] = [
		'panels' => ['test-panel' => '<h1>lazy content</h1>'],
		'time' => time(),
	];
	$_GET['_tracy_bar'] = 'lazy-panel.abcdef1234.test-panel';
	$defer = new Tracy\DeferredContent($storage);

	ob_start();
	Assert::true($defer->sendAssets());
	$response = ob_get_clean();

	Assert::same(['content' => '<h1>lazy content</h1>'], json_decode($response, true, flags: JSON_THROW_ON_ERROR));
	Assert::same([], $storage->data['lazy-panels']);
	unset($_GET['_tracy_bar']);
});


test('replacing a lazy panel clears the lazy flag', function () {
	$_SERVER['HTTP_X_TRACY_AJAX'] = 'abcdef1234';
	setHtmlMode();
	$storage = new LazyPanelSessionStorage;
	$defer = createLazyPanelDefer($storage);
	$lazyPanel = new LazyPanel;
	$eagerPanel = new LazyPanel;
	$bar = new Tracy\Bar;
	$bar->addPanel($lazyPanel, 'test.panel', lazy: true);
	$bar->addPanel($eagerPanel, 'test.panel');

	$bar->render($defer);
	$bar->renderLazyPanels($defer);

	Assert::same(0, $lazyPanel->tabCalls);
	Assert::same(0, $lazyPanel->panelCalls);
	Assert::same(1, $eagerPanel->tabCalls);
	Assert::same(1, $eagerPanel->panelCalls);
	Assert::hasNotKey('lazy-panels', $storage->data);

	unset($_SERVER['HTTP_X_TRACY_AJAX']);
});


test('session cleanup preserves all panels from one request', function () {
	$_SERVER['HTTP_X_TRACY_AJAX'] = 'abcdef1234';
	setHtmlMode();
	$storage = new LazyPanelSessionStorage;
	$defer = createLazyPanelDefer($storage);
	$bar = new Tracy\Bar;
	for ($i = 0; $i < 11; $i++) {
		$bar->addPanel(new LazyPanel, 'test-' . $i, lazy: true);
	}

	$bar->render($defer);
	$bar->renderLazyPanels($defer);
	$defer->clean();

	Assert::count(1, $storage->data['lazy-panels']);
	Assert::count(11, $storage->data['lazy-panels']['abcdef1234']['panels']);

	unset($_SERVER['HTTP_X_TRACY_AJAX']);
});
