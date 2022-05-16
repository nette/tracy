<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tracy;


/**
 * Debug Bar.
 */
class Bar
{
	/** @var IBarPanel[] */
	private array $panels = [];
	private bool $loaderRendered = false;


	/**
	 * Add custom panel.
	 * @return static
	 */
	public function addPanel(IBarPanel $panel, ?string $id = null): self
	{
		if ($id === null) {
			$c = 0;
			do {
				$id = $panel::class . ($c++ ? "-$c" : '');
			} while (isset($this->panels[$id]));
		}

		$this->panels[$id] = $panel;
		return $this;
	}


	/**
	 * Returns panel with given id
	 */
	public function getPanel(string $id): ?IBarPanel
	{
		return $this->panels[$id] ?? null;
	}


	/**
	 * Renders loading <script>
	 * @internal
	 */
	public function renderLoader(DeferredContent $defer): void
	{
		if (!$defer->isAvailable()) {
			throw new \LogicException('Start session before Tracy is enabled.');
		}

		$this->loaderRendered = true;
		$requestId = $defer->getRequestId();
		$nonce = Helpers::getNonce();
		$async = true;
		require __DIR__ . '/assets/loader.phtml';
	}


	/**
	 * Renders debug bar.
	 */
	public function render(DeferredContent $defer): void
	{
		$redirectQueue = &$defer->getItems('redirect');
		$requestId = $defer->getRequestId();

		if (Helpers::isAjax()) {
			if ($defer->isAvailable()) {
				$defer->addSetup('Tracy.Debug.loadAjax', $this->renderPartial('ajax', '-ajax:' . $requestId));
			}
		} elseif (Helpers::isRedirect()) {
			if ($defer->isAvailable()) {
				$redirectQueue[] = ['content' => $this->renderPartial('redirect', '-r' . count($redirectQueue)), 'time' => time()];
			}
		} elseif (Helpers::isHtmlMode()) {
			if (preg_match('#^Content-Length:#im', implode("\n", headers_list()))) {
				Debugger::log(new \LogicException('Tracy cannot display the Bar because the Content-Length header is being sent'), Debugger::EXCEPTION);
			}

			$content = $this->renderPartial('main');

			foreach (array_reverse($redirectQueue) as $item) {
				$content['bar'] .= $item['content']['bar'];
				$content['panels'] .= $item['content']['panels'];
			}

			$redirectQueue = null;

			$content = '<div id=tracy-debug-bar>' . $content['bar'] . '</div>' . $content['panels'];

			if ($this->loaderRendered) {
				$defer->addSetup('Tracy.Debug.init', $content);

			} else {
				$nonce = Helpers::getNonce();
				$async = false;
				Debugger::removeOutputBuffers(false);
				require __DIR__ . '/assets/loader.phtml';
			}
		}
	}


	private function renderPartial(string $type, string $suffix = ''): array
	{
		$panels = $this->renderPanels($suffix);

		return [
			'bar' => Helpers::capture(function () use ($type, $panels) {
				require __DIR__ . '/assets/bar.phtml';
			}),
			'panels' => Helpers::capture(function () use ($type, $panels) {
				require __DIR__ . '/assets/panels.phtml';
			}),
		];
	}


	private function renderPanels(string $suffix = ''): array
	{
		set_error_handler(function (int $severity, string $message, string $file, int $line) {
			if (error_reporting() & $severity) {
				throw new \ErrorException($message, 0, $severity, $file, $line);
			}
		});

		$obLevel = ob_get_level();
		$panels = [];

		foreach ($this->panels as $id => $panel) {
			$idHtml = preg_replace('#[^a-z0-9]+#i', '-', $id) . $suffix;
			try {
				$tab = (string) $panel->getTab();
				$panelHtml = $tab ? $panel->getPanel() : null;

			} catch (\Throwable $e) {
				while (ob_get_level() > $obLevel) { // restore ob-level if broken
					ob_end_clean();
				}

				$idHtml = "error-$idHtml";
				$tab = "Error in $id";
				$panelHtml = "<h1>Error: $id</h1><div class='tracy-inner'>" . nl2br(Helpers::escapeHtml($e)) . '</div>';
				unset($e);
			}

			$panels[] = (object) ['id' => $idHtml, 'tab' => $tab, 'panel' => $panelHtml];
		}

		restore_error_handler();
		return $panels;
	}
}
