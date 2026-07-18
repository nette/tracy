<?php

declare(strict_types=1);

require __DIR__ . '/../src/tracy.php';

use Tracy\Debugger;
use Tracy\IBarPanel;

// For security reasons, Tracy is visible only on localhost.
// You may force Tracy to run in development mode by passing the Debugger::Development instead of Debugger::Detect.
Debugger::enable(Debugger::Detect, __DIR__ . '/log');


/**
 * Example: A normal (eager) panel — getPanel() is called during the request.
 */
class NormalPanel implements IBarPanel
{
	public function getTab(): string
	{
		return '<span title="Normal Panel">⚡ Normal</span>';
	}

	public function getPanel(): string
	{
		return '<h1>Normal Panel</h1>'
			. '<div class="tracy-inner">'
			. '<p>This panel was rendered <strong>during the request</strong> (eager).</p>'
			. '<p>Time: ' . date('H:i:s') . '</p>'
			. '</div>';
	}
}


/**
 * Example: A "heavy" panel that simulates expensive computation.
 * When registered with lazy: true, getPanel() is NOT called during the request.
 * Instead, it is rendered in the shutdown function and served via AJAX on click.
 */
class HeavyPanel implements IBarPanel
{
	public function getTab(): string
	{
		return '<span title="Heavy Panel (lazy)">🐢 Heavy</span>';
	}

	public function getPanel(): string
	{
		// Simulate expensive operation (e.g., database profiling, API calls)
		usleep(500_000); // 500ms delay

		return '<h1>Heavy Panel (lazy loaded)</h1>'
			. '<div class="tracy-inner">'
			. '<p>This panel was rendered <strong>after the response</strong> (lazy).</p>'
			. '<p>It simulates a 500ms expensive computation.</p>'
			. '<p>Time: ' . date('H:i:s') . '</p>'
			. '<table><tr><th>Key</th><th>Value</th></tr>'
			. '<tr><td>PHP Version</td><td>' . PHP_VERSION . '</td></tr>'
			. '<tr><td>Memory Peak</td><td>' . number_format(memory_get_peak_usage() / 1024 / 1024, 2) . ' MB</td></tr>'
			. '<tr><td>Extensions</td><td>' . count(get_loaded_extensions()) . ' loaded</td></tr>'
			. '</table>'
			. '</div>';
	}
}


/**
 * Example: Another lazy panel showing database-like profiling info.
 */
class DatabasePanel implements IBarPanel
{
	public function getTab(): string
	{
		return '<span title="Database Panel (lazy)">🗄️ DB</span>';
	}

	public function getPanel(): string
	{
		usleep(300_000); // 300ms delay

		$queries = [
			['SELECT * FROM users WHERE id = 1', '0.5ms'],
			['SELECT * FROM posts WHERE user_id = 1 ORDER BY created_at DESC LIMIT 10', '2.1ms'],
			['UPDATE users SET last_login = NOW() WHERE id = 1', '0.3ms'],
		];

		$html = '<h1>Database Panel (lazy loaded)</h1>'
			. '<div class="tracy-inner">'
			. '<p>Simulated database queries — rendered lazily after the response was sent.</p>'
			. '<table><tr><th>#</th><th>Query</th><th>Time</th></tr>';

		foreach ($queries as $i => [$query, $time]) {
			$html .= '<tr><td>' . ($i + 1) . '</td><td><code>' . htmlspecialchars($query) . '</code></td><td>' . $time . '</td></tr>';
		}

		$html .= '</table></div>';
		return $html;
	}
}


// Register panels:
// Normal panel (eager) — rendered during the request
Debugger::getBar()->addPanel(new NormalPanel, 'example-normal');

// Heavy panel — lazy: true means getPanel() is deferred to shutdown function
Debugger::getBar()->addPanel(new HeavyPanel, 'example-heavy', lazy: true);

// Database panel — also lazy
Debugger::getBar()->addPanel(new DatabasePanel, 'example-database', lazy: true);

?>
<!DOCTYPE html><html class=arrow><link rel="stylesheet" href="assets/style.css">

<h1>Tracy: Lazy Panel Loading Demo</h1>

<h2>How it works</h2>
<p>This demo shows the <code>lazy: true</code> parameter for <code>Debugger::getBar()->addPanel()</code>.</p>

<ul>
	<li><strong>⚡ Normal</strong> — A regular panel. Its <code>getPanel()</code> is called during the request.</li>
	<li><strong>🐢 Heavy</strong> — A lazy panel simulating a 500ms expensive operation. Content loads on click.</li>
	<li><strong>🗄️ DB</strong> — A lazy panel simulating database query profiling. Content loads on click.</li>
</ul>

<h2>Usage</h2>
<pre><code>// Register a lazy panel — getPanel() is NOT called during the request
Debugger::getBar()->addPanel(new MyExpensivePanel, 'my-panel', lazy: true);
</code></pre>

<p>Lazy panels have their <code>getTab()</code> called normally (so the tab is always visible),
but <code>getPanel()</code> is deferred to a shutdown function. The content is stored in the session
and fetched via AJAX when you click or hover over the panel tab.</p>

<p>This is useful for panels that perform expensive operations like database profiling,
API call logging, or heavy data analysis — they won't slow down your page response time.</p>

<?php

if (Debugger::$productionMode) {
	echo '<p><b>For security reasons, Tracy is visible only on localhost. Look into the source code to see how to enable Tracy.</b></p>';
}
