<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tracy;

use ErrorException;


/**
 * @internal
 */
final class DevelopmentStrategy
{
	public function __construct(
		private Bar $bar,
		private BlueScreen $blueScreen,
		private DeferredContent $defer,
	) {
	}


	public function initialize(): void
	{
	}


	public function handleException(\Throwable $exception, bool $firstTime): void
	{
		if (Helpers::isAjax() && $this->defer->isAvailable()) {
			$this->blueScreen->renderToAjax($exception, $this->defer);

		} elseif ($firstTime && Helpers::isHtmlMode()) {
			$this->blueScreen->render($exception);

		} else {
			$this->renderExceptionCli($exception);
		}
	}


	private function renderExceptionCli(\Throwable $exception): void
	{
		try {
			$logFile = Debugger::log($exception, Debugger::EXCEPTION);
		} catch (\Throwable $e) {
			echo "$exception\nTracy is unable to log error: {$e->getMessage()}\n";
			return;
		}

		if ($logFile && !headers_sent()) {
			header("X-Tracy-Error-Log: $logFile", replace: false);
		}

		if (Helpers::detectColors() && @is_file($exception->getFile())) {
			echo "\n\n" . CodeHighlighter::highlightPhpCli(file_get_contents($exception->getFile()), $exception->getLine()) . "\n";
		}

		echo "$exception\n" . ($logFile ? "\n(stored in $logFile)\n" : '');
		if ($logFile && Debugger::$browser) {
			exec(Debugger::$browser . ' ' . escapeshellarg(strtr($logFile, Debugger::$editorMapping)));
		}
	}


	public function handleError(
		int $severity,
		string $message,
		string $file,
		int $line,
	): void
	{
		if (function_exists('ini_set')) {
			$oldDisplay = ini_set('display_errors', '1');
		}

		if (
			(is_bool(Debugger::$strictMode) ? Debugger::$strictMode : (Debugger::$strictMode & $severity)) // $strictMode
			&& !isset($_GET['_tracy_skip_error'])
		) {
			$e = new ErrorException($message, 0, $severity, $file, $line);
			@$e->skippable = true; // dynamic properties are deprecated since PHP 8.2
			Debugger::exceptionHandler($e);
			exit(255);
		}

		$message = Helpers::errorTypeToString($severity) . ': ' . Helpers::improveError($message);
		$count = &$this->bar->getPanel('Tracy:warnings')->data["$file|$line|$message"];

		if (!$count++ && !Helpers::isHtmlMode() && !Helpers::isAjax()) {
			echo "\n$message in $file on line $line\n";
		}

		if (function_exists('ini_set')) {
			ini_set('display_errors', $oldDisplay);
		}
	}


	public function sendAssets(): bool
	{
		return $this->defer->sendAssets();
	}


	public function renderLoader(): void
	{
		$this->bar->renderLoader($this->defer);
	}


	public function renderBar(): void
	{
		if (function_exists('ini_set')) {
			ini_set('display_errors', '1');
		}

		$this->bar->render($this->defer);
	}
}
