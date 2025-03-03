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
	private const MaxMessageLength = 2000;

	/** @var string[] */
	public array $info = [];

	/** @var string[] paths to be collapsed in stack trace (e.g. core libraries) */
	public array $collapsePaths = [];

	public int $maxDepth = 5;
	public int $maxLength = 150;
	public int $maxItems = 100;

	/** @var callable|null  a callable returning true for sensitive data; fn(string $key, mixed $val): bool */
	public $scrubber;

	/** @var string[] */
	public array $keysToHide = [
		'password', 'passwd', 'pass', 'pwd', 'creditcard', 'credit card', 'cc', 'pin', 'authorization',
		self::class . '::$snapshot',
	];

	public bool $showEnvironment = true;

	/** @var callable[] */
	private array $panels = [];

	/** @var callable[] functions that returns action for exceptions */
	private array $actions = [];
	private array $fileGenerators = [];
	private ?array $snapshot = null;

	/** @var \WeakMap<\Fiber|\Generator> */
	private \WeakMap $fibers;


	public function __construct()
	{
		$this->collapsePaths = preg_match('#(.+/vendor)/tracy/tracy/src/Tracy/BlueScreen$#', strtr(__DIR__, '\\', '/'), $m)
			? [$m[1] . '/tracy', $m[1] . '/nette', $m[1] . '/latte']
			: [dirname(__DIR__)];
		$this->fileGenerators[] = [self::class, 'generateNewPhpFileContents'];
		$this->fibers = new \WeakMap;
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
	 * Add new file generator.
	 * @param  callable(string): ?string  $generator
	 * @return static
	 */
	public function addFileGenerator(callable $generator): self
	{
		$this->fileGenerators[] = $generator;
		return $this;
	}


	public function addFiber(\Fiber|\Generator $fiber): static
	{
		$this->fibers[$fiber] = true;
		return $this;
	}


	/**
	 * Renders blue screen.
	 */
	public function render(\Throwable $exception): void
	{
		if (!headers_sent()) {
			header('Content-Type: text/html; charset=UTF-8');
		}

		$this->renderTemplate($exception, __DIR__ . '/assets/page.phtml');
	}


	/** @internal */
	public function renderToAjax(\Throwable $exception, DeferredContent $defer): void
	{
		$defer->addSetup('Tracy.BlueScreen.loadAjax', Helpers::capture(fn() => $this->renderTemplate($exception, __DIR__ . '/assets/content.phtml')));
	}


	/**
	 * Renders blue screen to file (if file exists, it will not be overwritten).
	 */
	public function renderToFile(\Throwable $exception, string $file): bool
	{
		if ($handle = @fopen($file, 'x')) {
			ob_start(); // double buffer prevents sending HTTP headers in some PHP
			ob_start(function ($buffer) use ($handle): void { fwrite($handle, $buffer); }, 4096);
			$this->renderTemplate($exception, __DIR__ . '/assets/page.phtml', toScreen: false);
			ob_end_flush();
			ob_end_clean();
			fclose($handle);
			return true;
		}

		return false;
	}


	private function renderTemplate(\Throwable $exception, string $template, bool $toScreen = true): void
	{
		[$generators, $fibers] = $this->findGeneratorsAndFibers($exception);
		$headersSent = headers_sent($headersFile, $headersLine);
		$obStatus = Debugger::$obStatus;
		$showEnvironment = $this->showEnvironment && (!str_contains($exception->getMessage(), 'Allowed memory size'));
		$info = array_filter($this->info);
		$source = Helpers::getSource();
		$title = $exception instanceof \ErrorException
			? Helpers::errorTypeToString($exception->getSeverity())
			: get_debug_type($exception);
		$lastError = $exception instanceof \ErrorException || $exception instanceof \Error
			? null
			: error_get_last();

		if (function_exists('apache_request_headers')) {
			$httpHeaders = apache_request_headers();
		} else {
			$httpHeaders = array_filter($_SERVER, fn($k) => strncmp($k, 'HTTP_', 5) === 0, ARRAY_FILTER_USE_KEY);
			$httpHeaders = array_combine(array_map(fn($k) => strtolower(strtr(substr($k, 5), '_', '-')), array_keys($httpHeaders)), $httpHeaders);
		}

		$snapshot = &$this->snapshot;
		$snapshot = [];
		$dump = $this->getDumper();

		$css = array_map('file_get_contents', array_merge([
			__DIR__ . '/../assets/reset.css',
			__DIR__ . '/assets/bluescreen.css',
			__DIR__ . '/../assets/toggle.css',
			__DIR__ . '/../assets/table-sort.css',
			__DIR__ . '/../assets/tabs.css',
			__DIR__ . '/../Dumper/assets/dumper-light.css',
		], Debugger::$customCssFiles));
		$css = Helpers::minifyCss(implode('', $css));

		$nonceAttr = $toScreen ? Helpers::getNonceAttr() : null;
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

		if (preg_match('# ([\'"])(\w{3,}(?:\\\\\w{2,})+)\1#i', $ex->getMessage(), $m)) {
			$class = $m[2];
			if (
				!class_exists($class, false) && !interface_exists($class, false) && !trait_exists($class, false)
				&& ($file = Helpers::guessClassFile($class)) && !@is_file($file) // @ - may trigger error
			) {
				[$content, $line] = $this->generateNewFileContents($file, $class);
				$actions[] = [
					'link' => Helpers::editorUri($file, $line, 'create', '', $content),
					'label' => 'create class',
				];
			}
		}

		if (preg_match('# ([\'"])((?:/|[a-z]:[/\\\])\w[^\'"]+\.\w{2,5})\1#i', $ex->getMessage(), $m)) {
			$file = $m[2];
			if (@is_file($file)) { // @ - may trigger error
				$label = 'open';
				$content = '';
				$line = 1;
			} else {
				$label = 'create';
				[$content, $line] = $this->generateNewFileContents($file);
			}

			$actions[] = [
				'link' => Helpers::editorUri($file, $line, $label, '', $content),
				'label' => $label . ' file',
			];
		}

		$query = ($ex instanceof \ErrorException ? '' : get_debug_type($ex) . ' ')
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
		bool $php = true,
		int $column = 0,
	): ?string
	{
		$source = @file_get_contents($file); // @ file may not exist
		if ($source === false) {
			return null;
		}

		$source = $php
			? CodeHighlighter::highlightPhp($source, $line, $column)
			: '<pre class=tracy-code><div>' . CodeHighlighter::highlightLine(htmlspecialchars($source, ENT_IGNORE, 'UTF-8'), $line, $column) . '</div></pre>';

		if ($editor = Helpers::editorUri($file, $line)) {
			$source = substr_replace($source, ' title="Ctrl-Click to open in editor" data-tracy-href="' . Helpers::escapeHtml($editor) . '"', 4, 0);
		}

		return $source;
	}


	/**
	 * Returns syntax highlighted source code.
	 */
	public static function highlightPhp(string $source, int $line, int $lines = 15, int $column = 0): string
	{
		return CodeHighlighter::highlightPhp($source, $line, $column);
	}


	/**
	 * Returns highlighted line in HTML code.
	 */
	public static function highlightLine(string $html, int $line, int $lines = 15, int $column = 0): string
	{
		return CodeHighlighter::highlightLine($html, $line, $column);
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
		return fn($v, $k = null): string => Dumper::toHtml($v, [
			Dumper::DEPTH => $this->maxDepth,
			Dumper::TRUNCATE => $this->maxLength,
			Dumper::ITEMS => $this->maxItems,
			Dumper::SNAPSHOT => &$this->snapshot,
			Dumper::LOCATION => Dumper::LOCATION_CLASS,
			Dumper::SCRUBBER => $this->scrubber,
			Dumper::KEYS_TO_HIDE => $this->keysToHide,
		], $k);
	}


	public function formatMessage(\Throwable $exception): string
	{
		$msg = Helpers::encodeString(trim((string) $exception->getMessage()), self::MaxMessageLength, showWhitespaces: false);

		// highlight 'string'
		$msg = preg_replace(
			'#\'\S(?:[^\']|\\\\\')*\S\'|"\S(?:[^"]|\\\")*\S"#',
			'<i>$0</i>',
			$msg,
		);

		// clickable class & methods
		$msg = preg_replace_callback(
			'#(\w+\\\[\w\\\]+\w)(?:::(\w+))?#',
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
			$msg,
		);

		// clickable file name
		$msg = preg_replace_callback(
			'#([\w\\\/.:-]+\.(?:php|phpt|phtml|latte|neon))(?|:(\d+)| on line (\d+))?#',
			fn($m) => @is_file($m[1]) // @ - may trigger error
				? '<a href="' . Helpers::escapeHtml(Helpers::editorUri($m[1], isset($m[2]) ? (int) $m[2] : null)) . '" class="tracy-editor">' . $m[0] . '</a>'
				: $m[0],
			$msg,
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

		if (!str_contains($license, '<body')) {
			echo '<pre class="tracy-dump tracy-light">', Helpers::escapeHtml($info), '</pre>';
		} else {
			$info = str_replace('<table', '<table class="tracy-sortable"', $info);
			echo preg_replace('#^.+<body>|</body>.+\z|<hr />|<h1>Configuration</h1>#s', '', $info);
		}
	}


	/** @internal */
	private function generateNewFileContents(string $file, ?string $class = null): array
	{
		foreach (array_reverse($this->fileGenerators) as $generator) {
			$content = $generator($file, $class);
			if ($content !== null) {
				$line = 1;
				$pos = strpos($content, '$END$');
				if ($pos !== false) {
					$content = substr_replace($content, '', $pos, 5);
					$line = substr_count($content, "\n", 0, $pos) + 1;
				}

				return [$content, $line];
			}
		}

		return ['', 1];
	}


	/** @internal */
	public static function generateNewPhpFileContents(string $file, ?string $class = null): ?string
	{
		if (substr($file, -4) !== '.php') {
			return null;
		}

		$res = "<?php\n\ndeclare(strict_types=1);\n\n";
		if (!$class) {
			return $res . '$END$';
		}

		if ($pos = strrpos($class, '\\')) {
			$res .= 'namespace ' . substr($class, 0, $pos) . ";\n\n";
			$class = substr($class, $pos + 1);
		}

		return $res . "class $class\n{\n\$END\$\n}\n";
	}


	private function findGeneratorsAndFibers(object $object): array
	{
		$generators = $fibers = [];
		$add = function ($obj) use (&$generators, &$fibers) {
			if ($obj instanceof \Generator) {
				try {
					new \ReflectionGenerator($obj);
					$generators[spl_object_id($obj)] = $obj;
				} catch (\ReflectionException) {
				}
			} elseif ($obj instanceof \Fiber && $obj->isStarted() && !$obj->isTerminated()) {
				$fibers[spl_object_id($obj)] = $obj;
			}
		};

		foreach ($this->fibers as $k => $v) {
			$add($k);
		}

		Helpers::traverseValue($object, $add);
		return [$generators, $fibers];
	}
}
