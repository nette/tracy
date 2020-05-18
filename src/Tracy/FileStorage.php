<?php

namespace Tracy;


class FileStorage implements IStorage
{
	private const COOKIE_NAME = 'tracy-session';

	/** @var string */
	private $tempDir;

	/** @var string|null */
	private $storageFilePath;


	/**
	 * @param  string  $tempDir
	 */
	public function __construct($tempDir)
	{
		if (!is_dir($tempDir)) {
			throw new \RuntimeException("Temporary directory '{$this->tempDir}' needs to exist.");
		}

		$this->tempDir = $tempDir;

		if (isset($_COOKIE[self::COOKIE_NAME])) {
			$sessionId = $_COOKIE[self::COOKIE_NAME];
		} else {
			$sessionId = uniqid();
			setcookie(self::COOKIE_NAME, $sessionId, time() + 7200, '/');
		}

		$this->storageFilePath = $this->tempDir . DIRECTORY_SEPARATOR . 'tracy-session-' . $sessionId;
	}


	/**
	 * @return void
	 */
	public function initialize(): void
	{
	}


	/**
	 * @return bool
	 */
	public function isActive()
	{
		return true;
	}


	/**
	 * @param  array        $data
	 * @param  string       $key1
	 * @param  string|null  $key2
	 * @return void
	 */
	public function save($data, $key1, $key2 = null)
	{
		$lockFile = $this->storageFilePath . '.lock';
		$lockHandle = $this->lock($lockFile);

		$storageData = $this->loadFromStorage();

		if ($key2 !== null) {
			$storageData[$key1][$key2] = $data;
		} else {
			$storageData[$key1] = $data;
		}

		file_put_contents($this->storageFilePath, serialize($storageData));

		$this->unlock($lockFile, $lockHandle);
	}


	/**
	 * @param  string       $key1
	 * @param  string|null  $key2
	 * @return array
	 */
	public function load($key1, $key2 = null)
	{
		$storageData = $this->loadFromStorage();

		if ($key2 !== null) {
			return $storageData[$key1][$key2] ?? [];
		}

		return $storageData[$key1] ?? [];
	}


	/**
	 * @return array
	 */
	private function loadFromStorage()
	{
		$data = (string) @file_get_contents($this->storageFilePath); // intentionally @
		return @unserialize($data) ?: []; // intentionally @
	}


	/**
	 * @param  string  $lockFile
	 * @return resource
	 */
	private function lock($lockFile)
	{
		$handle = @fopen($lockFile, 'c+'); // intentionally @
		if ($handle === FALSE) {
			throw new \RuntimeException("Unable to create file '$lockFile' " . error_get_last()['message']);
		} elseif (!@flock($handle, LOCK_EX)) { // intentionally @
			throw new \RuntimeException("Unable to acquire exclusive lock on '$lockFile' ", error_get_last()['message']);
		}
		return $handle;
	}


	/**
	 * @param  string    $lockFile
	 * @param  resource  $lockHandle
	 * @return void
	 */
	private function unlock($lockFile, $lockHandle)
	{
		@flock($lockHandle, LOCK_UN); // intentionally @
		@fclose($lockHandle); // intentionally @
		@unlink($lockFile); // intentionally @
	}
}
