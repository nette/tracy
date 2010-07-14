<?php

/**
 * Test: Nette\Debug::timer()
 *
 * @author     David Grudl
 * @category   Nette
 * @package    Nette
 * @subpackage UnitTests
 */

use Nette\Debug;



require __DIR__ . '/../initialize.php';



Debug::timer();

sleep(1);

Debug::timer('foo');

sleep(1);

T::dump( round(Debug::timer(), 1) );

T::dump( round(Debug::timer('foo'), 1) );



__halt_compiler() ?>

------EXPECT------
2.0

1.0
