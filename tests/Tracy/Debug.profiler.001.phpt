<?php

/**
 * Test: Nette\Debug::enableProfiler()
 *
 * @author     David Grudl
 * @category   Nette
 * @package    Nette
 * @subpackage UnitTests
 */

/*use Nette\Debug;*/



require dirname(__FILE__) . '/../NetteTest/initialize.php';



Debug::$consoleMode = FALSE;
Debug::$productionMode = FALSE;

header('Content-Type: text/html');

Debug::enableProfiler();



__halt_compiler();

------EXPECT------

<style type="text/css">
%A%
</style>


<div id="netteProfilerContainer">
<div id="netteProfiler">
	<a id="netteProfilerIcon" href="#"><abbr>&#x25bc;</abbr></a
	><ul>
		<li>Elapsed time: <b>%a%</b> ms | Allocated memory: <b>%a%</b> kB
%A%</ul>
</div>
</div>


<script type="text/javascript">
/* <![CDATA[ */
%A%
/* ]]> */
</script>
