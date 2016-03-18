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
		@session_start(); // @ session may be already started or it is not possible to start session
		$session = & $_SESSION['__NF']['tracybar-2.3'];
		$redirect = preg_match('#^Location:#im', implode("\n", headers_list()));
		if ($redirect) {
			Dumper::fetchLiveData();
			Dumper::$livePrefix = count($session) . 'p';
		}

		$obLevel = ob_get_level();
		$panels = [];
		foreach ($this->panels as $id => $panel) {
			$idHtml = preg_replace('#[^a-z0-9]+#i', '-', $id);
			try {
				$tab = (string) $panel->getTab();
				$panelHtml = $tab ? (string) $panel->getPanel() : NULL;
				if ($tab && $panel instanceof \Nette\Diagnostics\IBarPanel) {
					$panelHtml = preg_replace('~(["\'.\s#])nette-(debug|inner|collapsed|toggle|toggle-collapsed)(["\'\s])~', '$1tracy-$2$3', $panelHtml);
					$panelHtml = str_replace('tracy-toggle-collapsed', 'tracy-toggle tracy-collapsed', $panelHtml);
				}
				$panels[] = ['id' => $idHtml, 'tab' => $tab, 'panel' => $panelHtml];

			} catch (\Throwable $e) {
			} catch (\Exception $e) {
			}
			if (isset($e)) {
				$panels[] = [
					'id' => "error-$idHtml",
					'tab' => "Error in $id",
					'panel' => '<h1>Error: ' . $id . '</h1><div class="tracy-inner">'
						. nl2br(htmlSpecialChars($e, ENT_IGNORE, 'UTF-8')) . '</div>',
				];
				while (ob_get_level() > $obLevel) { // restore ob-level if broken
					ob_end_clean();
				}
			}
		}

		if ($redirect) {
			$session[] = ['panels' => $panels, 'liveData' => Dumper::fetchLiveData()];
			return;
		}

		$liveData = Dumper::fetchLiveData();

		foreach (array_reverse((array) $session) as $reqId => $info) {
			$panels[] = [
				'tab' => '<span title="Previous request before redirect">previous</span>',
				'panel' => NULL,
				'previous' => TRUE,
			];
			foreach ($info['panels'] as $panel) {
				$panel['id'] .= '-' . $reqId;
				$panels[] = $panel;
			}
			$liveData = array_merge($liveData, $info['liveData']);
		}
		$session = NULL;

		require __DIR__ . '/assets/Bar/bar.phtml';
	}

}
