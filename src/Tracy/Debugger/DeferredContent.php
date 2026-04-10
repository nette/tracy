<?php declare(strict_types=1);

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Tracy;

use function array_slice, is_string, strlen;


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
		if (headers_sent($file, $line) || ob_get_length()) {
			throw new \LogicException(
				__METHOD__ . '() called after some output has been sent. '
				. ($file ? "Output started at $file:$line." : 'Try Tracy\OutputDebugger to find where output started.'),
			);
		}

		$asset = $_GET['_tracy_bar'] ?? null;
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
			__DIR__ . '/../Dumper/assets/dumper-light.css',
			__DIR__ . '/../Dumper/assets/dumper-dark.css',
		], Debugger::$customCssFiles));
		$barCss = file_get_contents(__DIR__ . '/../Bar/assets/bar.css') ?: throw new \RuntimeException('Cannot read bar.css');
		$bsCss = file_get_contents(__DIR__ . '/../BlueScreen/assets/bluescreen.css') ?: throw new \RuntimeException('Cannot read bluescreen.css');

		$js1 = array_map(fn($file) => '(function() {' . file_get_contents($file) . '})();', [
			__DIR__ . '/../Bar/assets/bar.js',
			__DIR__ . '/../assets/toggle.js',
			__DIR__ . '/../assets/table-sort.js',
			__DIR__ . '/../assets/tabs.js',
			__DIR__ . '/../assets/helpers.js',
			__DIR__ . '/../Dumper/assets/dumper.js',
			__DIR__ . '/../BlueScreen/assets/bluescreen.js',
		]);
		$js2 = array_map(file_get_contents(...), Debugger::$customJsFiles);

		$str = "'use strict';
(function(){
	var n = document.currentScript.getAttribute('nonce') || document.currentScript.nonce;
	function s(css, cls) {
		var el = document.createElement('style');
		el.setAttribute('nonce', n);
		el.className = cls;
		el.textContent = css;
		document.head.appendChild(el);
	}
	s(" . json_encode(Helpers::minifyCss(implode('', $sharedCss))) . ",'tracy-debug');
	s(" . json_encode(Helpers::minifyCss($barCss)) . ",'tracy-debug tracy-bar-css');
	s(" . json_encode(Helpers::minifyCss($bsCss)) . ",'tracy-debug tracy-bs-css');})
();\n" . implode('', $js1) . implode('', $js2);

		return $str;
	}


	public function clean(): void
	{
		foreach ($this->sessionStorage->getData() as &$items) {
			$items = array_slice((array) $items, -10, null, preserve_keys: true);
			$items = array_filter($items, fn($item) => isset($item['time']) && $item['time'] > time() - 60);
		}
	}
}
