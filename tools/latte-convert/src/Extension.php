<?php declare(strict_types=1);

namespace Tracy\Tools\Compiler;

use Latte;
use Latte\Compiler\Node;
use Latte\Compiler\Nodes\AuxiliaryNode;
use Latte\Compiler\Nodes\Html\ExpressionAttributeNode;
use Latte\Compiler\Nodes\Php\Expression\ArrayNode;
use Latte\Compiler\Nodes\PrintNode;
use Latte\Compiler\Nodes\TemplateNode;
use Latte\Compiler\NodeTraverser;
use Latte\Compiler\PrintContext;
use Latte\Essential\Nodes;
use Latte\Runtime\HtmlHelpers;


/**
 * Latte extension for Tracy standalone template compilation.
 * Registers only the tags Tracy templates use, with custom nodes where needed.
 */
class Extension extends Latte\Extension
{
	/** Functions that return pre-escaped HTML and should not be auto-escaped. */
	private const NoEscapePattern = '~Helpers::editorLink|BlueScreen::highlightFile|Dumper::toHtml|Dumper::formatSnapshotAttribute|\$dump\(|->formatMessage\(~';


	public function getTags(): array
	{
		return [
			// Standard Latte nodes (standalone-compatible, no $this references)
			'if' => Nodes\IfNode::create(...),
			'ifset' => Nodes\IfNode::create(...),
			'foreach' => Nodes\ForeachNode::create(...),
			'for' => Nodes\ForNode::create(...),
			'while' => Nodes\WhileNode::create(...),
			'switch' => Nodes\SwitchNode::create(...),
			'do' => Nodes\DoNode::create(...),
			'var' => Nodes\VarNode::create(...),
			'default' => Nodes\VarNode::create(...),
			'exitIf' => JumpNode::create(...),
			'continueIf' => JumpNode::create(...),
			'breakIf' => JumpNode::create(...),
			'=' => PrintNode::create(...),
			'l' => fn(Latte\Compiler\Tag $tag) => new Latte\Compiler\Nodes\TextNode('{', $tag->position),
			'r' => fn(Latte\Compiler\Tag $tag) => new Latte\Compiler\Nodes\TextNode('}', $tag->position),

			// Custom Tracy nodes (generate standalone PHP)
			'varType' => VarTypeNode::create(...),
			'use' => UseNode::create(...),
			'define' => DefineNode::create(...),
			'include' => IncludeNode::create(...),
			'try' => TryNode::create(...),
		];
	}


	public function getPasses(): array
	{
		return [
			'tracyAttributes' => $this->attributePass(...),
			'tracyPrintNode' => $this->printNodePass(...),
		];
	}


	/**
	 * Replaces ExpressionAttributeNode with Tracy-compatible inline code.
	 * Runs at compile-time, classifies attributes and generates standalone PHP.
	 */
	private function attributePass(TemplateNode $node): void
	{
		$noEscapePattern = self::NoEscapePattern;

		(new NodeTraverser)->traverse($node, function (Node $node) use ($noEscapePattern): ?Node {
			if (!$node instanceof ExpressionAttributeNode) {
				return null;
			}

			$attr = $node;
			$type = HtmlHelpers::classifyAttributeType($attr->name);
			$isArray = $attr->value instanceof ArrayNode;
			$hasJsonFilter = (bool) $attr->modifier->removeFilter('json');

			// list attributes with non-array values fall back to regular attribute handling
			if ($type === 'list' && !$isArray) {
				$type = '';
			}

			// Check if expression contains known HTML-generating functions
			$valueCode = $attr->value->print(new PrintContext);
			$shouldEscape = !$hasJsonFilter && !preg_match($noEscapePattern, $valueCode);

			return new AuxiliaryNode(
				function (PrintContext $context) use ($attr, $type, $shouldEscape, $hasJsonFilter): string {
					$value = $attr->value->print($context);
					if ($hasJsonFilter) {
						$value = 'Tracy\Helpers::jsonEncode(' . $value . ')';
					}
					$namePart = $attr->indentation . $attr->name;
					$pos = $attr->value->position;
					$line = $pos ? "/* pos $pos->line" . ($pos->column ? ":$pos->column" : '') . ' */' : '';

					if (!$shouldEscape) {
						$q = $hasJsonFilter ? "'" : '"';
						return 'echo ($ʟ_tmp = (' . $value . ')) === null ? \'\' : '
							. var_export($namePart . '=' . $q, true) . ' . $ʟ_tmp . ' . var_export($q, true) . " $line;\n";
					}

					return match ($type) {
						'bool' => "echo ($value) ? " . var_export($namePart, true) . " : '' $line;\n",
						'list' => 'echo ($ʟ_tmp = array_filter(' . $value . ')) ? '
							. var_export($namePart . '="', true) . " . Tracy\\Helpers::escapeHtml(implode(' ', \$ʟ_tmp)) . '\"' : '' $line;\n",
						default => 'echo ($ʟ_tmp = (' . $value . ')) === null ? \'\' : '
							. var_export($namePart . '="', true) . " . Tracy\\Helpers::escapeHtml(\$ʟ_tmp) . '\"' $line;\n",
					};
				},
				[$attr->value],
			);
		});
	}


	/**
	 * Handles |json filter and auto-disables escaping for known HTML-generating functions.
	 */
	private function printNodePass(TemplateNode $node): void
	{
		(new NodeTraverser)->traverse($node, function (Node $node): ?Node {
			if (!$node instanceof PrintNode) {
				return null;
			}

			// |json filter → wrap in Tracy\Helpers::jsonEncode()
			if ($node->modifier->removeFilter('json')) {
				$node->modifier->escape = false;
				$origExpr = $node->expression;
				$node->expression = new Latte\Compiler\Nodes\Php\Expression\AuxiliaryNode(
					fn(PrintContext $context) => 'Tracy\Helpers::jsonEncode(' . $origExpr->print($context) . ')',
					[$origExpr],
				);
				return $node;
			}

			// Disable escaping for functions that return pre-escaped HTML
			if ($node->modifier->escape) {
				$code = $node->expression->print(new PrintContext);
				if (preg_match(self::NoEscapePattern, $code)) {
					$node->modifier->escape = false;
				}
			}

			return null;
		});
	}
}
