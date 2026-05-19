<?php declare(strict_types=1);

/**
 * Custom Latte nodes for Tracy standalone template compilation.
 * These nodes generate standalone PHP code without Latte runtime dependencies.
 */

namespace Tracy\Tools\Compiler;

use Latte\CompileException;
use Latte\Compiler\Nodes\AreaNode;
use Latte\Compiler\Nodes\Php\ExpressionNode;
use Latte\Compiler\Nodes\Php\Scalar;
use Latte\Compiler\Nodes\StatementNode;
use Latte\Compiler\PrintContext;
use Latte\Compiler\Tag;
use Latte\Compiler\TemplateParser;
use Latte\Compiler\Token;


/**
 * {varType Type $var}
 * Generates PHPDoc comment (standard Latte VarTypeNode outputs nothing).
 */
class VarTypeNode extends StatementNode
{
	public string $typeStr;
	public string $variable;


	public static function create(Tag $tag): static
	{
		$tag->expectArguments();
		$node = new static;
		$typeNode = $tag->parser->parseType();
		$node->typeStr = $typeNode ? $typeNode->type : '';
		$token = $tag->parser->stream->consume(Token::Php_Variable);
		$node->variable = $token->text;
		return $node;
	}


	public function print(PrintContext $context): string
	{
		return "/** @var $this->typeStr $this->variable */\n";
	}


	public function &getIterator(): \Generator
	{
		false && yield;
	}
}


/**
 * {use Tracy\Helpers}
 * Generates PHP use statement.
 */
class UseNode extends StatementNode
{
	public string $namespace;


	public static function create(Tag $tag): static
	{
		$tag->expectArguments();
		$node = new static;
		// Consume all remaining tokens as the namespace path
		$parts = [];
		while (!$tag->parser->stream->is(Token::End)) {
			$parts[] = $tag->parser->stream->consume()->text;
		}
		$node->namespace = implode('', $parts);
		return $node;
	}


	public function print(PrintContext $context): string
	{
		return 'use ' . $this->namespace . ";\n";
	}


	public function &getIterator(): \Generator
	{
		false && yield;
	}
}


/**
 * {define name $param1, $param2} ... {/define}
 * Generates $_blocks closure (standard Latte DefineNode generates class methods).
 */
class DefineNode extends StatementNode
{
	public string $name;
	public string $params;
	public AreaNode $content;


	/** @return \Generator<int, ?list<string>, array{AreaNode, ?Tag}, static> */
	public static function create(Tag $tag, TemplateParser $parser): \Generator
	{
		$tag->expectArguments();
		$node = $tag->node = new static;

		// Parse block name
		$tag->parser->stream->tryConsume('#');
		$nameExpr = $tag->parser->parseUnquotedStringOrExpression();
		if ($nameExpr instanceof Scalar\StringNode) {
			$node->name = $nameExpr->value;
		} else {
			throw new CompileException('Block name must be a static string.', $tag->position);
		}

		// Parse parameters as raw string
		$tag->parser->stream->tryConsume(',');
		$params = [];
		while (!$tag->parser->stream->is(Token::End)) {
			$params[] = $tag->parser->stream->consume()->text;
		}
		$node->params = implode('', $params);

		[$node->content, $endTag] = yield;
		if ($endTag) {
			$endTag->parser->stream->tryConsume($node->name);
		}

		return $node;
	}


	public function print(PrintContext $context): string
	{
		$content = $this->content->print($context);
		return '$_blocks[' . var_export($this->name, true) . '] = function (' . $this->params . ") use (&\$_blocks) {\n"
			. $content
			. "};\n";
	}


	public function &getIterator(): \Generator
	{
		yield $this->content;
	}
}


/**
 * {include name $args} or {include 'file.phtml'}
 * Block include: $_blocks['name']($args)
 * File include: require __DIR__ . '/file.phtml'
 */
class IncludeNode extends StatementNode
{
	public bool $isFile = false;
	public string $name;
	public string $args;


	public static function create(Tag $tag, TemplateParser $parser): static
	{
		$tag->outputMode = $tag::OutputRemoveIndentation;
		$tag->expectArguments();

		$node = new static;

		// Try to detect block vs file keyword
		$tag->parser->tryConsumeTokenBeforeUnquotedString('block', 'file');
		$tag->parser->stream->tryConsume('#');

		$nameExpr = $tag->parser->parseUnquotedStringOrExpression();
		if ($nameExpr instanceof Scalar\StringNode) {
			$node->name = $nameExpr->value;
			$node->isFile = str_contains($node->name, '.');
		} else {
			throw new CompileException('Include name must be a static string.', $tag->position);
		}

		// Parse remaining arguments as raw string
		$tag->parser->stream->tryConsume(',');
		$args = [];
		while (!$tag->parser->stream->is(Token::End)) {
			$args[] = $tag->parser->stream->consume()->text;
		}
		$node->args = implode('', $args);

		return $node;
	}


	public function print(PrintContext $context): string
	{
		if ($this->isFile) {
			return 'require __DIR__ . ' . var_export('/' . $this->name, true) . ";\n";
		}

		return '$_blocks[' . var_export($this->name, true) . "]($this->args);\n";
	}


	public function &getIterator(): \Generator
	{
		false && yield;
	}
}


/**
 * {try} ... {rollback} ... {/try}
 * Generates simple try/catch (standard Latte TryNode uses output buffering).
 */
class TryNode extends StatementNode
{
	public AreaNode $try;
	public ?AreaNode $catch = null;


	/** @return \Generator<int, ?list<string>, array{AreaNode, ?Tag}, static> */
	public static function create(Tag $tag): \Generator
	{
		$node = $tag->node = new static;
		[$node->try, $nextTag] = yield ['rollback', 'else'];
		if ($nextTag?->name === 'rollback' || $nextTag?->name === 'else') {
			[$node->catch] = yield;
		}

		return $node;
	}


	public function print(PrintContext $context): string
	{
		$code = "try {\n" . $this->try->print($context) . "}\n";
		if ($this->catch) {
			$code .= "catch (\\Throwable) {\n" . $this->catch->print($context) . "}\n";
		} else {
			$code .= "catch (\\Throwable) {}\n";
		}

		return $code;
	}


	public function &getIterator(): \Generator
	{
		yield $this->try;
		if ($this->catch) {
			yield $this->catch;
		}
	}
}


/**
 * {exitIf $cond}, {continueIf $cond}, {breakIf $cond}
 * Simplified JumpNode without parent-tag context checks.
 */
class JumpNode extends StatementNode
{
	public string $type;
	public ExpressionNode $condition;


	public static function create(Tag $tag): static
	{
		$tag->expectArguments();
		$tag->outputMode = $tag::OutputRemoveIndentation;
		$node = new static;
		$node->type = $tag->name;
		$node->condition = $tag->parser->parseExpression();
		return $node;
	}


	public function print(PrintContext $context): string
	{
		return $context->format(
			"if (%node) %line %raw\n",
			$this->condition,
			$this->position,
			match ($this->type) {
				'breakIf' => 'break;',
				'continueIf' => 'continue;',
				'exitIf' => 'return;',
				default => 'return;',
			},
		);
	}


	public function &getIterator(): \Generator
	{
		yield $this->condition;
	}
}
