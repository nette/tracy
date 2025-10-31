<?php

/**
 * Test: Tracy\Dumper::toText() enum/flags property & PhpToken
 */

declare(strict_types=1);

use Tester\Assert;
use Tracy\Dumper;

require __DIR__ . '/../bootstrap.php';


Dumper::addEnumProperty(PhpToken::class, 'id', array_keys(get_defined_constants(true)['tokenizer']));

$tokens = PhpToken::tokenize('<?php echo(10);');

Assert::match(
	<<<'XX'
		array (6)
		   0 => PhpToken #%d%
		   |  id: T_OPEN_TAG (%d%)
		   |  text: '<?php '
		   |  line: 1
		   |  pos: 0
		   1 => PhpToken #%d%
		   |  id: T_ECHO (%d%)
		   |  text: 'echo'
		   |  line: 1
		   |  pos: 6
		   2 => PhpToken #%d%
		   |  id: 40
		   |  text: '('
		   |  line: 1
		   |  pos: 10
		   3 => PhpToken #%d%
		   |  id: T_LNUMBER (%d%)
		   |  text: '10'
		   |  line: 1
		   |  pos: 11
		   4 => PhpToken #%d%
		   |  id: 41
		   |  text: ')'
		   |  line: 1
		   |  pos: 13
		   5 => PhpToken #%d%
		   |  id: 59
		   |  text: ';'
		   |  line: 1
		   |  pos: 14
		XX,
	Dumper::toText($tokens),
);
