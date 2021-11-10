<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tracy\Bridges\Nette;

use Nette;
use Tracy;
use Tracy\BlueScreen\Action;
use Tracy\Helpers;


class BlueScreenExtensionForNette extends Tracy\Bar\Extension
{
	public function getAction(\Throwable $e): ?Action
	{
		if (!$e instanceof Nette\MemberAccessException && !$e instanceof \LogicException) {
			return null;
		}
		$loc = $e->getTrace()[$e instanceof Nette\MemberAccessException ? 1 : 0];
		if (preg_match('#Cannot (?:read|write to) an undeclared property .+::\$(\w+), did you mean \$(\w+)\?#A', $e->getMessage(), $m)) {
			return new Action(
				'fix it',
				Helpers::editorUri($loc['file'], $loc['line'], 'fix', '->' . $m[1], '->' . $m[2])
			);
		} elseif (preg_match('#Call to undefined (static )?method .+::(\w+)\(\), did you mean (\w+)\(\)?#A', $e->getMessage(), $m)) {
			$operator = $m[1] ? '::' : '->';
			return new Action(
				'fix it',
				Helpers::editorUri($loc['file'], $loc['line'], 'fix', $operator . $m[2] . '(', $operator . $m[3] . '(')
			);
		}
		return null;
	}
}
