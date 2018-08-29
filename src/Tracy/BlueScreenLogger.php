<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Tracy;


/**
 * Logger which generates BlueScreen file.
 */
class BlueScreenLogger implements ILogger
{
	/** @var string|null name of the directory where errors should be logged */
	public $directory;

	/** @var BlueScreen */
	private $blueScreen;


	public function __construct(?string $directory, ?BlueScreen $blueScreen = null)
	{
		$this->directory = $directory;
		$this->blueScreen = $blueScreen;
	}


	public function log($message, string $priority = self::INFO): ?string
	{
		if (!$this->directory) {
			throw new \LogicException('Logging directory is not specified.');

		} elseif (!is_dir($this->directory)) {
			throw new \RuntimeException("Logging directory '$this->directory' is not found or is not directory.");
		}

		if ($message instanceof \Throwable) {
			return $this->logException($message);
		}

		return null;
	}


	public function getExceptionFile(\Throwable $exception): string
	{
		while ($exception) {
			$data[] = [
				get_class($exception), $exception->getMessage(), $exception->getCode(), $exception->getFile(), $exception->getLine(),
				array_map(function ($item) { unset($item['args']); return $item; }, $exception->getTrace()),
			];
			$exception = $exception->getPrevious();
		}
		$hash = substr(md5(serialize($data)), 0, 10);
		$dir = strtr($this->directory . '/', '\\/', DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR);
		foreach (new \DirectoryIterator($this->directory) as $file) {
			if (strpos($file->getBasename(), $hash)) {
				return $dir . $file;
			}
		}
		return $dir . 'exception--' . @date('Y-m-d--H-i') . "--$hash.html"; // @ timezone may not be set
	}


	/**
	 * Logs exception to the file if file doesn't exist.
	 * @return string logged error filename
	 */
	protected function logException(\Throwable $exception, ?string $file = null): string
	{
		$file = $file ?? $this->getExceptionFile($exception);
		$bs = $this->blueScreen ?? new BlueScreen;
		$bs->renderToFile($exception, $file);
		return $file;
	}
}
