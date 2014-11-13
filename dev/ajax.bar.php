<?php

require __DIR__ . '/../src/tracy.php';


class Panel implements Tracy\IBarPanel, Tracy\IAsyncHandler
{
	/** @var string */
	private $id;

	public function getTab()
	{
		return 'AJAX';
	}


	public function getPanel()
	{
		$parameters = array('id' => $this->id);
		$json = htmlSpecialChars(json_encode($parameters), ENT_QUOTES);
		$id = htmlSpecialChars($this->id);

		ob_start();
		include __DIR__ . '/ajax.bar.phtml';
		return ob_get_clean();
	}


	public function handleAsyncCall($parameters)
	{
		return '<strong>' . microtime(TRUE) . '</strong>'
			. '<br><xmp>' . var_export($parameters, TRUE) . '</xmp>';
	}


	public function setHandlerId($id)
	{
		$this->id = $id;
	}

}

Tracy\Debugger::enable();

Tracy\Debugger::getBar()->addPanel($panel = new Panel);
Tracy\Debugger::addAsyncHandler($panel);

Tracy\Debugger::getBar()->addPanel($panel = new Panel);
Tracy\Debugger::addAsyncHandler($panel);

Tracy\Debugger::getBar()->addPanel($panel = new Panel);
Tracy\Debugger::addAsyncHandler($panel);
