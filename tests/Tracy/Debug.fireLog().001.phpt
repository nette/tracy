<?php

/**
 * Test: Nette\Debug::fireLog()
 *
 * @author     David Grudl
 * @category   Nette
 * @package    Nette
 * @subpackage UnitTests
 */

/*use Nette\Debug;*/



require dirname(__FILE__) . '/../NetteTest/initialize.php';



// Setup environment
$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 Gecko/2008070208 Firefox/3.0.1 FirePHP/0.1.0.3';

Debug::$consoleMode = FALSE;
Debug::$productionMode = FALSE;



$arr = array(10, 20.2, TRUE, FALSE, NULL, 'hello', array('key1' => 'val1', 'key2' => TRUE), (object) array('key1' => 'val1', 'key2' => TRUE));

// will show in Firebug "Console" tab
Debug::fireLog('Hello World'); // Debug::LOG
Debug::fireLog('Info message', Debug::INFO);
Debug::fireLog('Warn message', Debug::WARN);
Debug::fireLog('Error message', Debug::ERROR);
Debug::fireLog($arr);

Debug::fireLog(
	array(
		array('SQL Statement', 'Time', 'Result'), // table header
		array('SELECT * FROM foo', '0.02', array('field1', 'field2')), // 1. row
		array('SELECT * FROM bar', '0.04', array('field1', 'field2')), // 2. row
	),
	'TABLE',
	'2 SQL queries took 0.06 seconds' // table title
);



__halt_compiler() ?>

------EXPECT------

---EXPECTHEADERS---
X-Wf-nette-1-1-n1: |[{"Type":"LOG","Label":null},"Hello World"]|
X-Wf-nette-1-1-n2: |[{"Type":"INFO","Label":null},"Info message"]|
X-Wf-nette-1-1-n3: |[{"Type":"WARN","Label":null},"Warn message"]|
X-Wf-nette-1-1-n4: |[{"Type":"ERROR","Label":null},"Error message"]|
X-Wf-nette-1-1-n5: |[{"Type":"LOG","Label":null},[10,20.2,true,false,null,"hello",{"key1":"val1","key2":true},"object stdClass"]]|
X-Wf-Protocol-nette: http://meta.wildfirehq.org/Protocol/JsonStream/0.2
X-Wf-nette-Plugin-1: http://meta.firephp.org/Wildfire/Plugin/FirePHP/Library-FirePHPCore/0.2.0
X-Wf-nette-Structure-1: http://meta.firephp.org/Wildfire/Structure/FirePHP/FirebugConsole/0.1
X-Wf-nette-1-1-n6: |[{"Type":"TABLE","Label":"2 SQL queries took 0.06 seconds"},[["SQL Statement","Time","Result"],["SELECT * FROM foo","0.02",["field1","field2"]],["SELECT * FROM bar","0.04",["field1","field2"]]]]|
