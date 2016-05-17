<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Tracy;


/**
 * Debug Bar.
 */
class Bar
{
	/** @var IBarPanel[] */
	private $panels = [];

	/** @var bool */
	private $dispatched;


	/**
	 * Add custom panel.
	 * @param  IBarPanel
	 * @param  string
	 * @return self
	 */
	public function addPanel(IBarPanel $panel, $id = NULL)
	{
		if ($id === NULL) {
			$c = 0;
			do {
				$id = get_class($panel) . ($c++ ? "-$c" : '');
			} while (isset($this->panels[$id]));
		}
		$this->panels[$id] = $panel;
		return $this;
	}


	/**
	 * Returns panel with given id
	 * @param  string
	 * @return IBarPanel|NULL
	 */
	public function getPanel($id)
	{
		return isset($this->panels[$id]) ? $this->panels[$id] : NULL;
	}


	/**
	 * Renders debug bar.
	 * @return void
	 */
	public function render()
	{
		if (!Helpers::isHtmlMode() && !Helpers::isAjax()) {
			return;
		}

		$previousBars = & $_SESSION['_tracy']['redirect'];
		$isRedirect = preg_match('#^Location:#im', implode("\n", headers_list()));
		$suffix = '';
		if ($isRedirect) {
			Dumper::fetchLiveData();
			Dumper::$livePrefix = count($previousBars) . 'p';
			$suffix = '-r' . count($previousBars);
		} elseif (Helpers::isAjax()) {
			$suffix = '-ajax';
		}

		$obLevel = ob_get_level();
		$panels = [];

		set_error_handler(function ($severity, $message, $file, $line) {
			if (error_reporting() & $severity) {
				throw new \ErrorException($message, 0, $severity, $file, $line);
			}
		});

		foreach ($this->panels as $id => $panel) {
			$idHtml = preg_replace('#[^a-z0-9]+#i', '-', $id) . $suffix;
			try {
				$tab = (string) $panel->getTab();
				$panelHtml = $tab ? (string) $panel->getPanel() : NULL;
				if ($tab && $panel instanceof \Nette\Diagnostics\IBarPanel) {
					$panelHtml = preg_replace('~(["\'.\s#])nette-(debug|inner|collapsed|toggle|toggle-collapsed)(["\'\s])~', '$1tracy-$2$3', $panelHtml);
					$panelHtml = str_replace('tracy-toggle-collapsed', 'tracy-toggle tracy-collapsed', $panelHtml);
				}

			} catch (\Throwable $e) {
			} catch (\Exception $e) {
			}
			if (isset($e)) {
				while (ob_get_level() > $obLevel) { // restore ob-level if broken
					ob_end_clean();
				}
				$idHtml = "error-$idHtml";
				$tab = "Error in $id";
				$panelHtml = "<h1>Error: $id</h1><div class='tracy-inner'>" . nl2br(Helpers::escapeHtml($e)) . '</div>';
			}
			$panels[] = (object) ['id' => $idHtml, 'tab' => $tab, 'panel' => $panelHtml];
		}

		restore_error_handler();

		$liveData = Dumper::fetchLiveData();

		if ($isRedirect) {
			$previousBars[] = ['panels' => $panels, 'liveData' => $liveData];
			return;
		}

		$rows[] = (object) ['type' => Helpers::isAjax() ? 'ajax' : 'main', 'panels' => $panels];
		foreach (array_reverse((array) $previousBars) as $info) {
			$rows[] = (object) ['type' => 'redirect', 'panels' => $info['panels']];
			$liveData += $info['liveData'];
		}
		$previousBars = NULL;

		ob_start(function () {});
		require __DIR__ . '/assets/Bar/panels.phtml';
		require __DIR__ . '/assets/Bar/bar.phtml';
		$content = Helpers::fixEncoding(ob_get_clean());
		$contentId = NULL;

		if ($this->dispatched) {
			$contentId = Helpers::isAjax()
				? $_SERVER['HTTP_X_TRACY_AJAX'] . '-ajax'
				: substr(md5(uniqid('', TRUE)), 0, 10);

			$_SESSION['_tracy']['bar'][$contentId] = ['content' => $content, 'liveData' => $liveData];
		}

		if (Helpers::isHtmlMode()) {
			$stopXdebug = extension_loaded('xdebug') ? ['XDEBUG_SESSION_STOP' => 1] : [];
			require __DIR__ . '/assets/Bar/loader.phtml';
		}
	}


	/**
	 * Renders debug bar assets.
	 * @return bool
	 */
	public function dispatchAssets()
	{
		$asset = isset($_GET['_tracy_bar']) ? $_GET['_tracy_bar'] : NULL;
		if ($asset === 'css') {
			header('Content-Type: text/css');
			header('Cache-Control: max-age=864000');
			header_remove('Pragma');
			header_remove('Set-Cookie');
			readfile(__DIR__ . '/assets/Bar/bar.css');
			readfile(__DIR__ . '/assets/Toggle/toggle.css');
			readfile(__DIR__ . '/assets/Dumper/dumper.css');
			return TRUE;

		} elseif ($asset === 'js') {
			header('Content-Type: text/javascript');
			header('Cache-Control: max-age=864000');
			header_remove('Pragma');
			header_remove('Set-Cookie');
			readfile(__DIR__ . '/assets/Bar/bar.js');
			readfile(__DIR__ . '/assets/Toggle/toggle.js');
			readfile(__DIR__ . '/assets/Dumper/dumper.js');
			return TRUE;
		}
	}


	/**
	 * Renders debug bar content.
	 * @return bool
	 */
	public function dispatchContent()
	{
		$this->dispatched = TRUE;
		if (Helpers::isAjax()) {
			header('X-Tracy-Ajax: 1'); // session must be already locked
		}
		if (preg_match('#^content(-ajax)?.(\w+)$#', isset($_GET['_tracy_bar']) ? $_GET['_tracy_bar'] : '', $m)) {
			$session = & $_SESSION['_tracy']['bar'][$m[2] . $m[1]];
			header('Content-Type: text/javascript');
			header('Cache-Control: max-age=60');
			header_remove('Set-Cookie');
			if ($session) {
				$method = $m[1] ? 'loadAjax' : 'init';
				echo "Tracy.Debug.$method(", json_encode($session['content']), ', ', json_encode($session['liveData']), ');';
				$session = NULL;
			}
			return TRUE;
		}
	}

}
