<?php declare(strict_types=1);

namespace Tracy\TestFixtures;

use Tracy\Helpers;


/** @tracySkipLocation */
function annotated(): void
{
}


function plain(): void
{
}


function findCallerLocationWrapper(): ?array
{
	return Helpers::findCallerLocation([__DIR__]);
}
