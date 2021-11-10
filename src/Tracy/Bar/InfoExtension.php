<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tracy\Bar;

use Tracy\Debugger;


/**
 * @internal
 */
final class InfoExtension extends Extension
{
	/** @var array */
	public $cpuUsage;

	/** @var float */
	public $time;

	/** @var array */
	public $data;


	public function getPanel(): ?Panel
	{
		$this->time = microtime(true) - Debugger::$time;
		return Panel::capture(
			function () {
				$time = $this->time;
				require __DIR__ . '/panels/info.tab.phtml';
			},
			function () {
				$info = $this->getInfo();
				[$packages, $devPackages] = $this->getPackages();
				require __DIR__ . '/panels/info.panel.phtml';
			},
			$this->getId()
		);
	}


	public function getId(): string
	{
		return 'Tracy:info';
	}


	private function getInfo(): array
	{
		if (isset($this->cpuUsage) && $this->time) {
			foreach (getrusage() as $key => $val) {
				$this->cpuUsage[$key] -= $val;
			}
			$userUsage = -round(($this->cpuUsage['ru_utime.tv_sec'] * 1e6 + $this->cpuUsage['ru_utime.tv_usec']) / $this->time / 10000);
			$systemUsage = -round(($this->cpuUsage['ru_stime.tv_sec'] * 1e6 + $this->cpuUsage['ru_stime.tv_usec']) / $this->time / 10000);
		}

		$opcache = function_exists('opcache_get_status') ? @opcache_get_status() : null; // @ can be restricted
		$cachedFiles = isset($opcache['scripts']) ? array_intersect(array_keys($opcache['scripts']), get_included_files()) : [];
		$jit = $opcache['jit'] ?? null;

		$info = [
			'Execution time' => number_format($this->time * 1000, 1, '.', ' ') . ' ms',
			'CPU usage user + system' => isset($userUsage) ? (int) $userUsage . ' % + ' . (int) $systemUsage . ' %' : null,
			'Peak of allocated memory' => number_format(memory_get_peak_usage() / 1000000, 2, '.', ' ') . ' MB',
			'Included files' => count(get_included_files()),
			'Classes + interfaces + traits' => self::countClasses(get_declared_classes()) . ' + '
				. self::countClasses(get_declared_interfaces()) . ' + ' . self::countClasses(get_declared_traits()),
			'OPcache' => $opcache ? round(count($cachedFiles) * 100 / count(get_included_files())) . ' % cached' : '–',
			'JIT' => empty($jit['buffer_size']) ? '–' : round(($jit['buffer_size'] - $jit['buffer_free']) / $jit['buffer_size'] * 100) . ' % used',
			'Your IP' => self::formatIP($_SERVER['REMOTE_ADDR'] ?? null),
			'Server IP' => self::formatIP($_SERVER['SERVER_ADDR'] ?? null),
			'HTTP method / response code' => isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] . ' / ' . http_response_code() : null,
			'PHP' => PHP_VERSION,
			'Xdebug' => extension_loaded('xdebug') ? phpversion('xdebug') : null,
			'Tracy' => Debugger::VERSION,
			'Server' => $_SERVER['SERVER_SOFTWARE'] ?? null,
		];

		return array_map('strval', array_filter($info + (array) $this->data));
	}


	private static function countClasses(array $list): int
	{
		return count(array_filter($list, function (string $name): bool {
			return (new \ReflectionClass($name))->isUserDefined();
		}));
	}


	private static function formatIP(?string $ip): ?string
	{
		if ($ip === '127.0.0.1' || $ip === '::1') {
			$ip .= ' (localhost)';
		}
		return $ip;
	}


	private function getPackages(): array
	{
		$packages = $devPackages = [];
		if (class_exists('Composer\Autoload\ClassLoader', false)) {
			$baseDir = (function () {
				@include dirname((new \ReflectionClass('Composer\Autoload\ClassLoader'))->getFileName()) . '/autoload_psr4.php'; // @ may not exist
				return $baseDir;
			})();
			$composer = @json_decode((string) file_get_contents($baseDir . '/composer.lock')); // @ may not exist or be valid
			[$packages, $devPackages] = [
				(array) @$composer->packages,
				(array) @$composer->{'packages-dev'},
			]; // @ keys may not exist
			foreach ([&$packages, &$devPackages] as &$items) {
				array_walk($items, function ($package) {
					$package->hash = $package->source->reference ?? $package->dist->reference ?? null;
				}, $items);
				usort($items, function ($a, $b): int {
					return $a->name <=> $b->name;
				});
			}
		}
		return [$packages, $devPackages];
	}
}
