<?php declare(strict_types=1);

/**
 * Class declarations for PHPStan (see scanFiles in phpstan.neon), never loaded at runtime.
 * Covers ext-ds 2.0 classes missing from PHPStan's bundled phpstorm-stubs (they cover only ext-ds 1.x).
 */

namespace Ds;

final class Seq implements \IteratorAggregate, \ArrayAccess, \Countable, \JsonSerializable
{
	public const MIN_CAPACITY = 8;


	public function getIterator(): \Iterator
	{
		throw new \LogicException;
	}


	public function offsetExists(mixed $offset): bool
	{
		throw new \LogicException;
	}


	public function offsetGet(mixed $offset): mixed
	{
		throw new \LogicException;
	}


	public function offsetSet(mixed $offset, mixed $value): void
	{
		throw new \LogicException;
	}


	public function offsetUnset(mixed $offset): void
	{
		throw new \LogicException;
	}


	public function count(): int
	{
		throw new \LogicException;
	}


	public function jsonSerialize(): mixed
	{
		throw new \LogicException;
	}
}


final class Heap implements \IteratorAggregate, \Countable, \JsonSerializable
{
	public const MIN_CAPACITY = 8;


	public function getIterator(): \Iterator
	{
		throw new \LogicException;
	}


	public function count(): int
	{
		throw new \LogicException;
	}


	public function jsonSerialize(): mixed
	{
		throw new \LogicException;
	}
}
