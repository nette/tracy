<?php declare(strict_types=1);

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Tracy;

use function array_slice, is_string, json_encode, strlen;
use const JSON_INVALID_UTF8_SUBSTITUTE, JSON_UNESCAPED_SLASHES, JSON_UNESCAPED_UNICODE;


/**
 * @internal
 */
final class DeferredContent
{
	private readonly bool $deferred;
	private readonly string $requestId;
	private bool $useSession = false;


	public function __construct(
		private readonly SessionStorage $sessionStorage,
	) {
		$ajax = $_SERVER['HTTP_X_TRACY_AJAX'] ?? '';
		$this->deferred = (bool) preg_match('#^\w{10,15}$#D', $ajax);
		$this->requestId = $this->deferred ? $ajax : Helpers::createId();
	}


	public function isDeferred(): bool
	{
		return $this->deferred;
	}


	public function isAvailable(): bool
	{
		return $this->useSession && $this->sessionStorage->isAvailable();
	}


	public function getRequestId(): string
	{
		return $this->requestId;
	}


	/** @return array<mixed> */
	public function &getItems(string $key): array
	{
		$items = &$this->sessionStorage->getData()[$key];
		$items = (array) $items;
		return $items;
	}


	public function addSetup(string $method, mixed $argument): void
	{
		$argument = Helpers::jsonEncode($argument);
		$item = &$this->getItems('setup')[$this->requestId];
		$item['code'] = ($item['code'] ?? '') . "$method($argument);\n";
		$item['time'] = time();
	}


	public function sendAssets(): bool
	{
		$asset = $_GET['_tracy_bar'] ?? null;
		if (headers_sent($file, $line) || ob_get_length()) {
			if ($asset === null && !$this->deferred) { // nothing to send, repeated enable() is a no-op
				return false;
			}

			throw new \LogicException(
				__METHOD__ . '() called after some output has been sent. '
				. ($file ? "Output started at $file:$line." : 'Try Tracy\OutputDebugger to find where output started.'),
			);
		}
		if ($asset === 'js') {
			header('Content-Type: application/javascript; charset=UTF-8');
			header('Cache-Control: max-age=864000');
			header_remove('Pragma');
			header_remove('Set-Cookie');
			$str = $this->buildJsCss();
			header('Content-Length: ' . strlen($str));
			echo $str;
			flush();
			return true;
		}

		$this->useSession = $this->sessionStorage->isAvailable();
		if (!$this->useSession) {
			return false;
		}

		$this->clean();

		if (is_string($asset) && preg_match('#^content(-ajax)?\.(\w+)$#', $asset, $m)) {
			[, $ajax, $requestId] = $m;
			header('Content-Type: application/javascript; charset=UTF-8');
			header('Cache-Control: max-age=60');
			header_remove('Set-Cookie');
			$str = $ajax ? '' : $this->buildJsCss();
			$data = &$this->getItems('setup');
			$str .= $data[$requestId]['code'] ?? '';
			unset($data[$requestId]);
			header('Content-Length: ' . strlen($str));
			echo $str;
			flush();
			return true;
		}

		if (is_string($asset) && preg_match('#^lazy-panel\.([\w.+-]+)$#', $asset, $m)) {
			$key = $m[1];
			header('Content-Type: application/json; charset=UTF-8');
			header('Cache-Control: no-cache');
			header_remove('Set-Cookie');
			$lazyItems = &$this->getItems('lazy-panels');
			$content = $lazyItems[$key]['content'] ?? null;
			unset($lazyItems[$key]);
			$str = json_encode(['content' => $content], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
			header('Content-Length: ' . strlen($str));
			echo $str;
			flush();
			return true;
		}

		if ($this->deferred) {
			header('X-Tracy-Ajax: 1'); // session must be already locked
		}

		return false;
	}


	private function buildJsCss(): string
	{
		$sharedCss = array_map(file_get_contents(...), array_merge([
			__DIR__ . '/../assets/reset.css',
			__DIR__ . '/../assets/toggle.css',
			__DIR__ . '/../assets/table-sort.css',
			__DIR__ . '/../assets/tabs.css',
			__DIR__ . '/../Dumper/assets/dumper.css',
		], Debugger::$customCssFiles));
		$barCss = file_get_contents(__DIR__ . '/../Bar/assets/bar.css') ?: throw new \RuntimeException('Cannot read bar.css');
		$bsCss = file_get_contents(__DIR__ . '/../BlueScreen/assets/bluescreen.css') ?: throw new \RuntimeException('Cannot read bluescreen.css');

		$js1 = array_map(fn($file) => '(function() {' . file_get_contents($file) . '})();', [
			__DIR__ . '/../assets/helpers.js', // must run first, defines the Tracy.css registry helpers
			__DIR__ . '/../Bar/assets/bar.js',
			__DIR__ . '/../assets/toggle.js',
			__DIR__ . '/../assets/table-sort.js',
			__DIR__ . '/../assets/tabs.js',
			__DIR__ . '/../Dumper/assets/dumper.js',
			__DIR__ . '/../BlueScreen/assets/bluescreen.js',
		]);
		$js2 = array_map(file_get_contents(...), Debugger::$customJsFiles);

		// CSS is exposed via the Tracy.css registry and applied through adoptedStyleSheets,
		// nothing is ever injected into the host page's document.head
		$str = "'use strict';
(function(){
	var Tracy = window.Tracy = window.Tracy || {};
	Tracy.css = Object.assign(Tracy.css || {}, {
		shared: " . json_encode(Helpers::minifyCss(implode('', $sharedCss))) . ',
		bar: ' . json_encode(Helpers::minifyCss($barCss)) . ',
		bluescreen: ' . json_encode(Helpers::minifyCss($bsCss)) . '
	});})
();
' . implode('', $js1) . implode('', $js2);

		return $str;
	}


	public function clean(): void
	{
		foreach ($this->sessionStorage->getData() as &$items) {
			$items = array_slice((array) $items, -10, preserve_keys: true);
			$items = array_filter($items, fn($item) => isset($item['time']) && $item['time'] > time() - 60);
		}
	}
}
