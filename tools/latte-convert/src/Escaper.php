<?php declare(strict_types=1);

namespace Tracy\Tools\Compiler;

use Latte;


/**
 * Custom escaper that replaces Latte runtime escape functions with Tracy/PHP equivalents.
 * Preserves context-awareness (HTML text, attributes, JavaScript, CSS).
 */
class Escaper extends Latte\Compiler\Escaper
{
	public function escape(string $str): string
	{
		return match ($this->getContentType()) {
			Latte\ContentType::Html => match ($this->getState()) {
				self::HtmlText, self::HtmlAttribute, self::HtmlTag, self::HtmlBogusTag, self::HtmlComment
					=> 'Tracy\Helpers::escapeHtml(' . $str . ')',
				self::HtmlRawText
					=> 'Tracy\Helpers::jsonEncode(' . $str . ', true)',
				default => 'Tracy\Helpers::escapeHtml(' . $str . ')',
			},
			Latte\ContentType::Text => 'Tracy\Helpers::escapeMd(' . $str . ')',
			default => $str,
		};
	}


	public function escapeMandatory(string $str, ?Latte\Compiler\Position $position = null): string
	{
		return $str;
	}
}
