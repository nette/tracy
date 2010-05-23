<?php

/**
 * Test: Nette\Debug::timer()
 *
 * @author     David Grudl
 * @category   Nette
 * @package    Nette
 * @subpackage UnitTests
 */

/*use Nette\Debug;*/



require dirname(__FILE__) . '/../NetteTest/initialize.php';



Debug::timer();

sleep(1);

Debug::timer('foo');

sleep(1);

dump( round(Debug::timer(), 1) );

dump( round(Debug::timer('foo'), 1) );



__halt_compiler() ?>

------EXPECT------
float(2)

float(1)
