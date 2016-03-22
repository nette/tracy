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
	/** @var Session */
	private $session;

	/** @var IBarPanel[] */
	private $panels = [];


	public function __construct(Session $session)
	{
		$this->session = $session;
	}


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
		$previousBars = & $this->session->getContent()['redirect'];
		$isRedirect = preg_match('#^Location:#im', implode("\n", headers_list()));
		$suffix = '';
		if ($isRedirect) {
			Dumper::fetchLiveData();
			Dumper::$livePrefix = count($previousBars) . 'p';
			$suffix = '-r' . count($previousBars);
		}

		$obLevel = ob_get_level();
		$panels = [];

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
				$panel = "<h1>Error: $id</h1><div class='tracy-inner'>" . nl2br(htmlSpecialChars($e, ENT_IGNORE, 'UTF-8')) . '</div>';
			}
			$panels[] = (object) ['id' => $idHtml, 'tab' => $tab, 'panel' => $panelHtml];
		}

		$liveData = Dumper::fetchLiveData();

		if ($isRedirect) {
			$previousBars[] = ['panels' => $panels, 'liveData' => $liveData];
			return;
		}

		$rows[] = (object) ['type' => 'main', 'panels' => $panels];
		foreach (array_reverse((array) $previousBars) as $info) {
			$rows[] = (object) ['type' => 'redirect', 'panels' => $info['panels']];
			$liveData += $info['liveData'];
		}
		$previousBars = NULL;

		ob_start(function () {});
		require __DIR__ . '/assets/Bar/panels.phtml';
		require __DIR__ . '/assets/Bar/bar.phtml';
		$content = Helpers::fixEncoding(ob_get_clean());

		if ($this->session->getId()) {
			$contentId = md5(uniqid('', TRUE));
			$this->session->getContent()['bar'][$contentId] = ['content' => $content, 'liveData' => $liveData];
		}

		require __DIR__ . '/assets/Bar/loader.phtml';
	}


	/**
	 * Renders debug bar assets.
	 * @return bool
	 */
	public function dispatch()
	{
		$asset = isset($_GET['_tracy_bar']) ? $_GET['_tracy_bar'] : NULL;

		if (preg_match('#^content.(\w+)$#', $asset, $m)) {
			$session = & $this->session->getContent()['bar'];
			header('Content-Type: text/javascript');
			header('Cache-Control: max-age=60');
			if (isset($session[$m[1]])) {
				echo 'Tracy.Debug.init(', json_encode($session[$m[1]]['content']), ', ', json_encode($session[$m[1]]['liveData']), ');';
				unset($session[$m[1]]);
			}
			return TRUE;

		} elseif ($asset === 'css') {
			header('Content-Type: text/css');
			header('Cache-Control: max-age=864000');
			readfile(__DIR__ . '/assets/Bar/bar.css');
			readfile(__DIR__ . '/assets/Toggle/toggle.css');
			readfile(__DIR__ . '/assets/Dumper/dumper.css');
			return TRUE;

		} elseif ($asset === 'js') {
			header('Content-Type: text/javascript');
			header('Cache-Control: max-age=864000');
			readfile(__DIR__ . '/assets/Bar/bar.js');
			readfile(__DIR__ . '/assets/Toggle/toggle.js');
			readfile(__DIR__ . '/assets/Dumper/dumper.js');
			return TRUE;
		}
	}

}
