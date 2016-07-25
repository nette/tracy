<?php

namespace Tracy;

class MessageFormat
{

	/**
	 * @param  string|\Exception|\Throwable
	 * @return string
	 */
	public function formatMessage($message)
	{
		if ($message instanceof \Exception || $message instanceof \Throwable) {
			$tmp = [];
			while ($message) {
				$tmp[] = ($message instanceof \ErrorException
						? Helpers::errorTypeToString($message->getSeverity()) . ': ' . $message->getMessage()
						: Helpers::getClass($message) . ': ' . $message->getMessage()
					) . ' in ' . $message->getFile() . ':' . $message->getLine();
				$message = $message->getPrevious();
			}
			$message = implode($tmp, "\ncaused by ");

		} elseif (!is_string($message)) {
			$message = Dumper::toText($message);
		}

		return trim($message);
	}

	/**
	 * @param  string|\Exception|\Throwable
	 * @param  string Name of result exception file
	 * @return string
	 */
	public function formatLogLine($message, $exceptionFile = NULL)
	{
		return implode(' ', [
			@date('[Y-m-d H-i-s]'), // @ timezone may not be set
			preg_replace('#\s*\r?\n\s*#', ' ', $this->formatMessage($message)),
			' @  ' . Helpers::getSource(),
			$exceptionFile ? ' @@  ' . basename($exceptionFile) : NULL,
		]);
	}

}
