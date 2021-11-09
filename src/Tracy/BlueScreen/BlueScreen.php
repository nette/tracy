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
	public $maxDepth = 5;

	/** @var int  */
	public $maxLength = 150;

	/** @var callable|null  a callable returning true for sensitive data; fn(string $key, mixed $val): bool */
	public $scrubber;

	/** @var string[] */
	public $keysToHide = ['password', 'passwd', 'pass', 'pwd', 'creditcard', 'credit card', 'cc', 'pin', self::class . '::$snapshot'];

	/** @var bool */
	public $showEnvironment = true;

	/** @var BlueScreen\Extension[] */
	private $extensions = [];

	/** @var array */
	private $snapshot;


	public function __construct()
	{
		$this->collapsePaths = preg_match('#(.+/vendor)/tracy/tracy/src/Tracy/BlueScreen$#', strtr(__DIR__, '\\', '/'), $m)
			? [$m[1] . '/tracy', $m[1] . '/nette', $m[1] . '/latte']
			: [dirname(__DIR__)];
	}


	public function addExtension(BlueScreen\Extension $extension): self
	{
		$this->extensions[] = $extension;
		return $this;
	}


	/**
	 * Add custom panel as function (?\Throwable $e): ?array
	 */
	public function addPanel(callable $panel): self
	{
		return $this->addExtension(new BlueScreen\ExtensionAdapter($panel, true));
	}


	/**
	 * Add action.
	 */
	public function addAction(callable $action): self
	{
		return $this->addExtension(new BlueScreen\ExtensionAdapter($action, false));
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
			__DIR__ . '/../Dumper/assets/dumper-light.css',
		], Debugger::$customCssFiles));
		$css = Helpers::minifyCss(implode($css));

		$nonce = $toScreen ? Helpers::getNonce() : null;
		$actions = $toScreen ? $this->renderActions($exception) : [];

		require $template;
	}


	/** @return BlueScreen\Panel[] */
	private function renderPanels(\Throwable $ex, string $pos): array
	{
		$obLevel = ob_get_level();
		$panels = [];

		foreach ($this->extensions as $extension) {
			try {
				if ($panel = $extension->{"get{$pos}Panel"}($ex)) {
					$panels[] = $panel;
				}
			} catch (\Throwable $e) {
				while (ob_get_level() > $obLevel) { // restore ob-level if broken
					ob_end_clean();
				}
				$panels[] = new BlueScreen\Panel(
					'Error in panel ' . $extension->getId(),
					nl2br(Helpers::escapeHtml($e))
				);
			}
		}
		return $panels;
	}


	/** @return BlueScreen\Action[] */
	private function renderActions(\Throwable $ex): array
	{
		$actions = [];
		foreach ($this->extensions as $extension) {
			if ($action = $extension->getAction($ex)) {
				$actions[] = $action;
			}
		}

		if (
			property_exists($ex, 'tracyAction')
			&& !empty($ex->tracyAction['link'])
			&& !empty($ex->tracyAction['label'])
		) {
			$actions[] = new BlueScreen\Action($ex->tracyAction['label'], $ex->tracyAction['link']);
		}

		if (preg_match('# ([\'"])(\w{3,}(?:\\\\\w{3,})+)\1#i', $ex->getMessage(), $m)) {
			$class = $m[2];
			if (
				!class_exists($class, false) && !interface_exists($class, false) && !trait_exists($class, false)
				&& ($file = Helpers::guessClassFile($class)) && !is_file($file)
			) {
				$actions[] = new BlueScreen\Action(
					'create class',
					Helpers::editorUri($file, 1, 'create')
				);
			}
		}

		if (preg_match('# ([\'"])((?:/|[a-z]:[/\\\\])\w[^\'"]+\.\w{2,5})\1#i', $ex->getMessage(), $m)) {
			$file = $m[2];
			$label = is_file($file) ? 'open' : 'create';
			$actions[] = new BlueScreen\Action(
				$label . ' file',
				Helpers::editorUri($file, 1, $label)
			);
		}

		$query = ($ex instanceof \ErrorException ? '' : Helpers::getClass($ex) . ' ')
			. preg_replace('#\'.*\'|".*"#Us', '', $ex->getMessage());
		$actions[] = new BlueScreen\Action(
			'search',
			'https://www.google.com/search?sourceid=tracy&q=' . urlencode($query),
			true
		);

		if (
			$ex instanceof \ErrorException
			&& !empty($ex->skippable)
			&& preg_match('#^https?://#', $source = Helpers::getSource())
		) {
			$actions[] = new BlueScreen\Action(
				'skip error',
				$source . (strpos($source, '?') ? '&' : '?') . '_tracy_skip_error'
			);
		}
		return $actions;
	}


	/**
	 * Returns syntax highlighted source code.
	 */
	public static function highlightFile(string $file, int $line, int $lines = 15): ?string
	{
		$source = @file_get_contents($file); // @ file may not exist
		if ($source === false) {
			return null;
		}
		$source = static::highlightPhp($source, $line, $lines);
		if ($editor = Helpers::editorUri($file, $line)) {
			$source = substr_replace($source, ' title="Ctrl-Click to open in editor" data-tracy-href="' . Helpers::escapeHtml($editor) . '"', 4, 0);
		}
		return $source;
	}


	/**
	 * Returns syntax highlighted source code.
	 */
	public static function highlightPhp(string $source, int $line, int $lines = 15): string
	{
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
	 * Returns syntax highlighted source code to Terminal.
	 */
	public static function highlightPhpCli(string $file, int $line, int $lines = 15): ?string
	{
		$source = @file_get_contents($file); // @ file may not exist
		if ($source === false) {
			return null;
		}
		$s = self::highlightPhp($source, $line, $lines);

		$colors = [
			'color: ' . ini_get('highlight.comment') => '1;30',
			'color: ' . ini_get('highlight.default') => '1;36',
			'color: ' . ini_get('highlight.html') => '1;35',
			'color: ' . ini_get('highlight.keyword') => '1;37',
			'color: ' . ini_get('highlight.string') => '1;32',
			'line' => '1;30',
			'highlight' => "1;37m\e[41",
		];

		$stack = ['0'];
		$s = preg_replace_callback(
			'#<\w+(?: (class|style)=["\'](.*?)["\'])?[^>]*>|</\w+>#',
			function ($m) use ($colors, &$stack): string {
				if ($m[0][1] === '/') {
					array_pop($stack);
				} else {
					$stack[] = isset($m[2], $colors[$m[2]]) ? $colors[$m[2]] : '0';
				}
				return "\e[0m\e[" . end($stack) . 'm';
			},
			$s
		);
		$s = htmlspecialchars_decode(strip_tags($s), ENT_QUOTES | ENT_HTML5);
		return $s;
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
		return function ($v, $k = null): string {
			return Dumper::toHtml($v, [
				Dumper::DEPTH => $this->maxDepth,
				Dumper::TRUNCATE => $this->maxLength,
				Dumper::SNAPSHOT => &$this->snapshot,
				Dumper::LOCATION => Dumper::LOCATION_CLASS,
				Dumper::SCRUBBER => $this->scrubber,
				Dumper::KEYS_TO_HIDE => $this->keysToHide,
			], $k);
		};
	}


	public function formatMessage(\Throwable $exception): string
	{
		$msg = Helpers::encodeString(trim((string) $exception->getMessage()), self::MAX_MESSAGE_LENGTH, false);

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
				return '<a href="' . Helpers::escapeHtml(Helpers::editorUri($r->getFileName(), $r->getStartLine())) . '" class="tracy-editor">' . $m[0] . '</a>';
			},
			$msg
		);

		// clickable file name
		$msg = preg_replace_callback(
			'#([\w\\\\/.:-]+\.(?:php|phpt|phtml|latte|neon))(?|:(\d+)| on line (\d+))?#',
			function ($m) {
				return @is_file($m[1])
				? '<a href="' . Helpers::escapeHtml(Helpers::editorUri($m[1], isset($m[2]) ? (int) $m[2] : null)) . '" class="tracy-editor">' . $m[0] . '</a>'
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
			echo '<pre class="tracy-dump tracy-light">', Helpers::escapeHtml($info), '</pre>';
		} else {
			$info = str_replace('<table', '<table class="tracy-sortable"', $info);
			echo preg_replace('#^.+<body>|</body>.+\z#s', '', $info);
		}
	}
}
