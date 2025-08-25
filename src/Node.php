<?php

namespace SortedList;

/**
 * @internal
 * @template T of int|string
 */
final class Node
{
	/** @var T */
	public int|string $value;
	/** @var ?Node<T> */
	public ?Node $prev = null;
	/** @var ?Node<T> */
	public ?Node $next = null;

	/** @param T $value */
	public function __construct(int|string $value)
	{
		$this->value = $value;
	}
}
