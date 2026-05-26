<?php declare(strict_types=1);

namespace Tracy\Tools\Compiler;

use Latte;
use Latte\Compiler\Nodes\TemplateNode;
use Latte\Compiler\PhpHelpers;
use Latte\Compiler\PrintContext;


/**
 * Latte engine subclass that compiles templates to standalone PHP files
 * without Latte runtime dependency. Used for Tracy panel template compilation.
 */
class Engine extends Latte\Engine
{
	public function __construct()
	{
		parent::__construct();
		$this->setFeature(Latte\Feature::Dedent, true);
	}


	protected function addDefaultExtensions(): void
	{
		$this->addExtension(new Extension);
	}


	/**
	 * Generates standalone PHP code from AST (no class wrapper, no Latte runtime).
	 */
	public function generate(TemplateNode $node, string $name): string
	{
		$escaper = new Escaper($node->contentType);

		$features = [];
		foreach (Latte\Feature::cases() as $feature) {
			if ($this->hasFeature($feature)) {
				$features[$feature->name] = true;
			}
		}

		$context = new PrintContext($node->contentType, $features, $escaper);

		// Compile head (varType, use declarations)
		$head = $node->head->print($context);

		// Compile main content
		$main = $node->main->print($context);

		// Assemble standalone PHP
		$code = "<?php\n\ndeclare(strict_types=1);\n\n"
			. $head
			. $main;

		// Optimize echo statements
		$code = PhpHelpers::optimizeEcho($code);

		// Reformat
		$code = PhpHelpers::reformatCode($code);

		// Normalize <?php declare header to single line
		$code = str_replace("<?php\n\ndeclare(strict_types=1);\n", "<?php declare(strict_types=1);\n", $code);

		return $code;
	}
}
