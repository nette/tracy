<?php declare(strict_types=1);

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Tracy;


/**
 * Tracy Bar panel providing a plain-text (markdown) summary for AI agents.
 */
interface AgentPanel
{
	/**
	 * Returns markdown summary for AI agents, or null when there is nothing to report.
	 */
	function getAgentInfo(): ?string;
}
