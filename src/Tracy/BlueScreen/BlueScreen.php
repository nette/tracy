<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tracy;


/**
 * Red BlueScreen.
 */
class BlueScreen
{
	private const MAX_MESSAGE_LENGTH = 2000;

	/** @var string[] */
	public $info = [];

	/** @var string[] paths to be collapsed in stack trace (e.g. core libraries) */
	public $collapsePaths = [];

	/** @var int  */
	public $maxDepth = 3;

	/** @var int  */
	public $maxLength = 150;

	/** @var string[] */
	public $keysToHide = ['password', 'passwd', 'pass', 'pwd', 'creditcard', 'credit card', 'cc', 'pin'];

	/** @var bool */
	public $showEnvironment = true;

	/** @var callable[] */
	private $panels = [];

	/** @var callable[] functions that returns action for exceptions */
	private $actions = [];

	/** @var array */
	private $snapshot;


	public function __construct()
	{
		$this->collapsePaths[] = preg_match('#(.+/vendor)/tracy/tracy/src/Tracy/BlueScreen$#', strtr(__DIR__, '\\', '/'), $m)
			? $m[1]
			: __DIR__;
	}


	/**
	 * Add custom panel as function (?\Throwable $e): ?array
	 * @return static
	 */
	public function addPanel(callable $panel): self
	{
		if (!in_array($panel, $this->panels, true)) {
			$this->panels[] = $panel;
		}
		return $this;
	}


	/**
	 * Add action.
	 * @return static
	 */
	public function addAction(callable $action): self
	{
		$this->actions[] = $action;
		return $this;
	}


	/**
	 * Renders blue screen.
	 */
	public function render(\Throwable $exception): void
	{
		if (Helpers::isAjax() && session_status() === PHP_SESSION_ACTIVE) {
			$_SESSION['_tracy']['bluescreen'][$_SERVER['HTTP_X_TRACY_AJAX']] = [
				'content' => Helpers::capture(function () use ($exception) {
					$this->renderTemplate($exception, __DIR__ . '/assets/content.phtml');
				}),
				'time' => time(),
			];

		} else {
			if (!headers_sent()) {
				header('Content-Type: text/html; charset=UTF-8');
			}
			$this->renderTemplate($exception, __DIR__ . '/assets/page.phtml');
		}
	}


	/**
	 * Renders blue screen to file (if file exists, it will not be overwritten).
	 */
	public function renderToFile(\Throwable $exception, string $file): bool
	{
		if ($handle = @fopen($file, 'x')) {
			ob_start(); // double buffer prevents sending HTTP headers in some PHP
			ob_start(function ($buffer) use ($handle): void { fwrite($handle, $buffer); }, 4096);
			$this->renderTemplate($exception, __DIR__ . '/assets/page.phtml', false);
			ob_end_flush();
			ob_end_clean();
			fclose($handle);
			return true;
		}
		return false;
	}


	private function renderTemplate(\Throwable $exception, string $template, $toScreen = true): void
	{
		$showEnvironment = $this->showEnvironment && (strpos($exception->getMessage(), 'Allowed memory size') === false);
		$messageHtml = $this->formatMessage($exception);
		$info = array_filter($this->info);
		$source = Helpers::getSource();
		$title = $exception instanceof \ErrorException
			? Helpers::errorTypeToString($exception->getSeverity())
			: Helpers::getClass($exception);
		$lastError = $exception instanceof \ErrorException || $exception instanceof \Error
			? null
			: error_get_last();

		if (function_exists('apache_request_headers')) {
			$httpHeaders = apache_request_headers();
		} else {
			$httpHeaders = array_filter($_SERVER, function ($k) { return strncmp($k, 'HTTP_', 5) === 0; }, ARRAY_FILTER_USE_KEY);
			$httpHeaders = array_combine(array_map(function ($k) { return strtolower(strtr(substr($k, 5), '_', '-')); }, array_keys($httpHeaders)), $httpHeaders);
		}

		$snapshot = &$this->snapshot;
		$snapshot = [];
		$dump = $this->getDumper();

		$css = array_map('file_get_contents', array_merge([
			__DIR__ . '/assets/bluescreen.css',
			__DIR__ . '/../Toggle/toggle.css',
			__DIR__ . '/../TableSort/table-sort.css',
			__DIR__ . '/../Dumper/assets/dumper.css',
		], Debugger::$customCssFiles));
		$css = preg_replace('#\s+#u', ' ', implode($css));

		$nonce = $toScreen ? Helpers::getNonce() : null;
		$actions = $toScreen ? $this->renderActions($exception) : [];

		require $template;
	}


	/**
	 * @return \stdClass[]
	 */
	private function renderPanels(?\Throwable $ex): array
	{
		$obLevel = ob_get_level();
		$res = [];
		foreach ($this->panels as $callback) {
			try {
				$panel = $callback($ex);
				if (empty($panel['tab']) || empty($panel['panel'])) {
					continue;
				}
				$res[] = (object) $panel;
				continue;
			} catch (\Throwable $e) {
			}
			while (ob_get_level() > $obLevel) { // restore ob-level if broken
				ob_end_clean();
			}
			is_callable($callback, true, $name);
			$res[] = (object) [
				'tab' => "Error in panel $name",
				'panel' => nl2br(Helpers::escapeHtml($e)),
			];
		}
		return $res;
	}


	/**
	 * @return array[]
	 */
	private function renderActions(\Throwable $ex): array
	{
		$actions = [];
		foreach ($this->actions as $callback) {
			$action = $callback($ex);
			if (!empty($action['link']) && !empty($action['label'])) {
				$actions[] = $action;
			}
		}

		if (
			property_exists($ex, 'tracyAction')
			&& !empty($ex->tracyAction['link'])
			&& !empty($ex->tracyAction['label'])
		) {
			$actions[] = $ex->tracyAction;
		}

		if (preg_match('# ([\'"])(\w{3,}(?:\\\\\w{3,})+)\1#i', $ex->getMessage(), $m)) {
			$class = $m[2];
			if (
				!class_exists($class) && !interface_exists($class) && !trait_exists($class)
				&& ($file = Helpers::guessClassFile($class)) && !is_file($file)
			) {
				$actions[] = [
					'link' => Helpers::editorUri($file, 1, 'create'),
					'label' => 'create class',
				];
			}
		}

		if (preg_match('# ([\'"])((?:/|[a-z]:[/\\\\])\w[^\'"]+\.\w{2,5})\1#i', $ex->getMessage(), $m)) {
			$file = $m[2];
			$actions[] = [
				'link' => Helpers::editorUri($file, 1, $label = is_file($file) ? 'open' : 'create'),
				'label' => $label . ' file',
			];
		}

		$query = ($ex instanceof \ErrorException ? '' : Helpers::getClass($ex) . ' ')
			. preg_replace('#\'.*\'|".*"#Us', '', $ex->getMessage());
		$actions[] = [
			'link' => 'https://www.google.com/search?sourceid=tracy&q=' . urlencode($query),
			'label' => 'search',
			'external' => true,
		];

		if (
			$ex instanceof \ErrorException
			&& !empty($ex->skippable)
			&& preg_match('#^https?://#', $source = Helpers::getSource())
		) {
			$actions[] = [
				'link' => $source . (strpos($source, '?') ? '&' : '?') . '_tracy_skip_error',
				'label' => 'skip error',
			];
		}
		return $actions;
	}


	/**
	 * Returns syntax highlighted source code.
	 */
	public static function highlightFile(
		string $file,
		int $line,
		int $lines = 15,
		array $vars = [],
		array $keysToHide = []
	): ?string {
		$source = @file_get_contents($file); // @ file may not exist
		if ($source === false) {
			return null;
		}
		$source = static::highlightPhp($source, $line, $lines, $vars, $keysToHide);
		if ($editor = Helpers::editorUri($file, $line)) {
			$source = substr_replace($source, ' data-tracy-href="' . Helpers::escapeHtml($editor) . '"', 4, 0);
		}
		return $source;
	}


	/**
	 * Returns syntax highlighted source code.
	 */
	public static function highlightPhp(
		string $source,
		int $line,
		int $lines = 15,
		array $vars = [],
		array $keysToHide = []
	): string {
		if (function_exists('ini_set')) {
			ini_set('highlight.comment', '#998; font-style: italic');
			ini_set('highlight.default', '#000');
			ini_set('highlight.html', '#06B');
			ini_set('highlight.keyword', '#D24; font-weight: bold');
			ini_set('highlight.string', '#080');
		}

		$source = preg_replace('#(__halt_compiler\s*\(\)\s*;).*#is', '$1', $source);
		$source = str_replace(["\r\n", "\r"], "\n", $source);
		$source = explode("\n", highlight_string($source, true));
		$out = $source[0]; // <code><span color=highlight.html>
		$source = str_replace('<br />', "\n", $source[1]);
		$out .= static::highlightLine($source, $line, $lines);

		if ($vars) {
			$out = preg_replace_callback('#">\$(\w+)(&nbsp;)?</span>#', function (array $m) use ($vars, $keysToHide): string {
				if (array_key_exists($m[1], $vars)) {
					$dump = Dumper::toHtml($vars[$m[1]], [
						Dumper::DEPTH => 1,
						Dumper::KEYS_TO_HIDE => $keysToHide,
					]);
					return '" title="' . str_replace('"', '&quot;', trim(strip_tags($dump))) . $m[0];
				}
				return $m[0];
			}, $out);
		}

		$out = str_replace('&nbsp;', ' ', $out);
		return "<pre class='code'><div>$out</div></pre>";
	}


	/**
	 * Returns highlighted line in HTML code.
	 */
	public static function highlightLine(string $html, int $line, int $lines = 15): string
	{
		$source = explode("\n", "\n" . str_replace("\r\n", "\n", $html));
		$out = '';
		$spans = 1;
		$start = $i = max(1, min($line, count($source) - 1) - (int) floor($lines * 2 / 3));
		while (--$i >= 1) { // find last highlighted block
			if (preg_match('#.*(</?span[^>]*>)#', $source[$i], $m)) {
				if ($m[1] !== '</span>') {
					$spans++;
					$out .= $m[1];
				}
				break;
			}
		}

		$source = array_slice($source, $start, $lines, true);
		end($source);
		$numWidth = strlen((string) key($source));

		foreach ($source as $n => $s) {
			$spans += substr_count($s, '<span') - substr_count($s, '</span');
			$s = str_replace(["\r", "\n"], ['', ''], $s);
			preg_match_all('#<[^>]+>#', $s, $tags);
			if ($n == $line) {
				$out .= sprintf(
					"<span class='highlight'>%{$numWidth}s:    %s\n</span>%s",
					$n,
					strip_tags($s),
					implode('', $tags[0])
				);
			} else {
				$out .= sprintf("<span class='line'>%{$numWidth}s:</span>    %s\n", $n, $s);
			}
		}
		$out .= str_repeat('</span>', $spans) . '</code>';
		return $out;
	}


	/**
	 * Should a file be collapsed in stack trace?
	 * @internal
	 */
	public function isCollapsed(string $file): bool
	{
		$file = strtr($file, '\\', '/') . '/';
		foreach ($this->collapsePaths as $path) {
			$path = strtr($path, '\\', '/') . '/';
			if (strncmp($file, $path, strlen($path)) === 0) {
				return true;
			}
		}
		return false;
	}


	/** @internal */
	public function getDumper(): \Closure
	{
		$keysToHide = array_flip(array_map('strtolower', $this->keysToHide));

		return function ($v, $k = null) use ($keysToHide): string {
			if (is_string($k) && isset($keysToHide[strtolower($k)])) {
				return '<pre class="tracy-dump">' . Helpers::escapeHtml(Dumper::hideValue($v)) . '</pre>';
			}
			return Dumper::toHtml($v, [
				Dumper::DEPTH => $this->maxDepth,
				Dumper::TRUNCATE => $this->maxLength,
				Dumper::SNAPSHOT => &$this->snapshot,
				Dumper::LOCATION => Dumper::LOCATION_CLASS,
				Dumper::KEYS_TO_HIDE => $this->keysToHide,
			]);
		};
	}


	private function formatMessage(\Throwable $exception): string
	{
		$msg = Dumper::encodeString((string) $exception->getMessage(), self::MAX_MESSAGE_LENGTH);
		$msg = htmlspecialchars($msg, ENT_SUBSTITUTE, 'UTF-8');

		// highlight 'string'
		$msg = preg_replace(
			'#\'\S(?:[^\']|\\\\\')*\S\'|"\S(?:[^"]|\\\\")*\S"#',
			'<i>$0</i>',
			$msg
		);

		// clickable class & methods
		$msg = preg_replace_callback(
			'#(\w+\\\\[\w\\\\]+\w)(?:::(\w+))?#',
			function ($m) {
				if (isset($m[2]) && method_exists($m[1], $m[2])) {
					$r = new \ReflectionMethod($m[1], $m[2]);
				} elseif (class_exists($m[1], false) || interface_exists($m[1], false)) {
					$r = new \ReflectionClass($m[1]);
				}
				if (empty($r) || !$r->getFileName()) {
					return $m[0];
				}
				return '<a href="' . Helpers::escapeHtml(Helpers::editorUri($r->getFileName(), $r->getStartLine())) . '">' . $m[0] . '</a>';
			},
			$msg
		);

		// clickable file name
		$msg = preg_replace_callback(
			'#([\w\\\\/.:-]+\.(?:php|phpt|phtml|latte|neon))(?|:(\d+)| on line (\d+))?#',
			function ($m) {
				return @is_file($m[1])
				? '<a href="' . Helpers::escapeHtml(Helpers::editorUri($m[1], isset($m[2]) ? (int) $m[2] : null)) . '">' . $m[0] . '</a>'
				: $m[0];
			},
			$msg
		);

		return $msg;
	}


	private function renderPhpInfo(): void
	{
		ob_start();
		@phpinfo(INFO_LICENSE); // @ phpinfo may be disabled
		$license = ob_get_clean();
		ob_start();
		@phpinfo(INFO_CONFIGURATION | INFO_MODULES); // @ phpinfo may be disabled
		$info = ob_get_clean();

		if (strpos($license, '<body') === false) {
			echo '<pre class="tracy-dump">', Helpers::escapeHtml($info), '</pre>';
		} else {
			$info = str_replace('<table', '<table class="tracy-sortable"', $info);
			echo preg_replace('#^.+<body>|</body>.+\z#s', '', $info);
		}
	}
}
