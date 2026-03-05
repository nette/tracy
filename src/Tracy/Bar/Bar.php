<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tracy;

use function count;


/**
 * Debug Bar.
 */
class Bar
{
	/** @var IBarPanel[] */
	private array $panels = [];

	/** @var array<string, bool> panel ID => lazy flag */
	private array $lazyPanels = [];
	private bool $loaderRendered = false;


	/**
	 * Add custom panel.
	 * @param bool $lazy  If true, panel content is rendered after the response is sent
	 *                    and loaded via AJAX when the user clicks on the tab.
	 *                    Use for panels whose getPanel() is expensive and not needed on every request.
	 */
	public function addPanel(IBarPanel $panel, ?string $id = null, bool $lazy = false): static
	{
		if ($id === null) {
			$c = 0;
			do {
				$id = $panel::class . ($c++ ? "-$c" : '');
			} while (isset($this->panels[$id]));
		}

		$this->panels[$id] = $panel;
		if ($lazy) {
			$this->lazyPanels[$id] = true;
		}

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
		$async = true;
		require __DIR__ . '/dist/loader.phtml';
	}


	/**
	 * Renders debug bar.
	 */
	public function render(DeferredContent $defer): void
	{
		$redirectQueue = &$defer->getItems('redirect');
		$requestId = $defer->getRequestId();

		if ($defer->isDeferred()) {
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
				$async = false;
				Debugger::removeOutputBuffers(errorOccurred: false);
				require __DIR__ . '/dist/loader.phtml';
			}
		}
	}


	/** @return array{bar: string, panels: string} */
	private function renderPartial(string $type, string $suffix = ''): array
	{
		$panels = $this->renderPanels($suffix);

		return [
			'bar' => Helpers::capture(function () use ($type, $panels) {
				require __DIR__ . '/dist/bar.phtml';
			}),
			'panels' => Helpers::capture(function () use ($type, $panels) {
				require __DIR__ . '/dist/panels.phtml';
			}),
		];
	}


	/** @return list<\stdClass> */
	private function renderPanels(string $suffix = ''): array
	{
		set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
			if (error_reporting() & $severity) {
				throw new \ErrorException($message, 0, $severity, $file, $line);
			}

			return true;
		});

		$obLevel = ob_get_level();
		$panels = [];

		foreach ($this->panels as $id => $panel) {
			$idHtml = preg_replace('#[^a-z0-9]+#i', '-', $id) . $suffix;
			$lazy = isset($this->lazyPanels[$id]);
			try {
				$tab = (string) $panel->getTab();
				if ($lazy && $tab) {
					$panelHtml = null; // will be rendered later via shutdown function
				} else {
					$panelHtml = $tab ? $panel->getPanel() : null;
				}

			} catch (\Throwable $e) {
				while (ob_get_level() > $obLevel) { // restore ob-level if broken
					ob_end_clean();
				}

				$idHtml = "error-$idHtml";
				$tab = "Error in $id";
				$panelHtml = "<h1>Error: $id</h1><div class='tracy-inner'>" . nl2br(Helpers::escapeHtml($e)) . '</div>';
				$lazy = false;
				unset($e);
			}

			$panels[] = (object) ['id' => $idHtml, 'tab' => $tab, 'panel' => $panelHtml, 'lazy' => $lazy];
		}

		restore_error_handler();
		return $panels;
	}


	/**
	 * Renders lazy panels in shutdown function and stores them in session.
	 * @internal
	 */
	public function renderLazyPanels(DeferredContent $defer): void
	{
		if (!$defer->isAvailable()) {
			return;
		}

		set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
			if (error_reporting() & $severity) {
				throw new \ErrorException($message, 0, $severity, $file, $line);
			}

			return true;
		});

		$obLevel = ob_get_level();

		foreach ($this->panels as $id => $panel) {
			if (!isset($this->lazyPanels[$id])) {
				continue;
			}

			try {
				$tab = (string) $panel->getTab();
				$panelHtml = $tab ? $panel->getPanel() : null;
			} catch (\Throwable $e) {
				while (ob_get_level() > $obLevel) {
					ob_end_clean();
				}

				$panelHtml = "<h1>Error: $id</h1><div class='tracy-inner'>" . nl2br(Helpers::escapeHtml($e)) . '</div>';
				unset($e);
			}

			if ($panelHtml !== null) {
				$icons = '<div class="tracy-icons">'
					. '<a href="#" data-tracy-action="window" title="open in window">&curren;</a>'
					. '<a href="#" data-tracy-action="close" title="close window">&times;</a>'
					. '</div>';
				$lazyItems = &$defer->getItems('lazy-panels');
				$lazyItems[$defer->getRequestId() . '.' . preg_replace('#[^a-z0-9]+#i', '-', $id)] = [
					'content' => $panelHtml . "\n" . $icons,
					'time' => time(),
				];
			}
		}

		restore_error_handler();
	}
}
