<?php

declare(strict_types=1);
namespace SortedList;

/**
 * Utility class providing common comparator functions for SortedLinkedList.
 * 
 * This class offers pre-built comparators for common sorting needs,
 * eliminating the need to write custom comparison functions for basic cases.
 * 
 * @example
 * ```php
 * // Case-insensitive string sorting
 * $list = new SortedLinkedList(Comparators::ciAsc());
 * 
 * // Descending integer sorting  
 * $numbers = new SortedLinkedList(Comparators::intDesc());
 * 
 * // Natural string sorting (version numbers, etc.)
 * $versions = new SortedLinkedList(Comparators::natural());
 * ```
 */
final class Comparators
{
	/**
	 * Case-insensitive ascending string comparator.
	 * 
	 * Uses strcasecmp() for locale-independent case-insensitive comparison.
	 * 
	 * @return callable(string, string): int
	 * 
	 * @example
	 * ```php
	 * $list = new SortedLinkedList(Comparators::ciAsc());
	 * $list->addAll(['banana', 'Apple', 'cherry']);
	 * // Result: ['Apple', 'banana', 'cherry']
	 * ```
	 */
	public static function ciAsc(): callable
	{
		return static fn(string $a, string $b): int => strcasecmp($a, $b);
	}

	/**
	 * Case-insensitive descending string comparator.
	 * 
	 * @return callable(string, string): int
	 * 
	 * @example
	 * ```php
	 * $list = new SortedLinkedList(Comparators::ciDesc());
	 * $list->addAll(['banana', 'Apple', 'cherry']);
	 * // Result: ['cherry', 'banana', 'Apple']
	 * ```
	 */
	public static function ciDesc(): callable
	{
		return static fn(string $a, string $b): int => strcasecmp($b, $a);
	}

	/**
	 * Ascending integer comparator.
	 * 
	 * Explicitly for integers (though default behavior is the same).
	 * 
	 * @return callable(int, int): int
	 * 
	 * @example
	 * ```php
	 * $list = new SortedLinkedList(Comparators::intAsc());
	 * $list->addAll([5, 1, 3]);
	 * // Result: [1, 3, 5]
	 * ```
	 */
	public static function intAsc(): callable
	{
		return static fn(int $a, int $b): int => $a <=> $b;
	}

	/**
	 * Descending integer comparator.
	 * 
	 * @return callable(int, int): int
	 * 
	 * @example
	 * ```php
	 * $list = new SortedLinkedList(Comparators::intDesc());
	 * $list->addAll([5, 1, 3]);
	 * // Result: [5, 3, 1]
	 * ```
	 */
	public static function intDesc(): callable
	{
		return static fn(int $a, int $b): int => $b <=> $a;
	}

	/**
	 * Natural order string comparator.
	 * 
	 * Uses strnatcmp() for "natural order" comparison, useful for
	 * version numbers, filenames with numbers, etc.
	 * 
	 * @return callable(string, string): int
	 * 
	 * @example
	 * ```php
	 * $list = new SortedLinkedList(Comparators::natural());
	 * $list->addAll(['file10.txt', 'file2.txt', 'file1.txt']);
	 * // Result: ['file1.txt', 'file2.txt', 'file10.txt']
	 * // (instead of: ['file1.txt', 'file10.txt', 'file2.txt'])
	 * ```
	 */
	public static function natural(): callable
	{
		return static fn(string $a, string $b): int => strnatcmp($a, $b);
	}

	/**
	 * Natural order case-insensitive string comparator.
	 * 
	 * Combines natural ordering with case-insensitive comparison.
	 * 
	 * @return callable(string, string): int
	 * 
	 * @example
	 * ```php
	 * $list = new SortedLinkedList(Comparators::naturalCi());
	 * $list->addAll(['File10.txt', 'file2.txt', 'FILE1.txt']);
	 * // Result: ['FILE1.txt', 'file2.txt', 'File10.txt']
	 * ```
	 */
	public static function naturalCi(): callable
	{
		return static fn(string $a, string $b): int => strnatcasecmp($a, $b);
	}

	/**
	 * Reverse any comparator function.
	 * 
	 * Takes an existing comparator and reverses its ordering.
	 * 
	 * @param callable $comparator The original comparator to reverse
	 * @return callable The reversed comparator
	 * 
	 * @example
	 * ```php
	 * // Reverse natural ordering
	 * $list = new SortedLinkedList(Comparators::reverse(Comparators::natural()));
	 * $list->addAll(['file1.txt', 'file2.txt', 'file10.txt']);
	 * // Result: ['file10.txt', 'file2.txt', 'file1.txt']
	 * ```
	 */
	public static function reverse(callable $comparator): callable
	{
		return static fn($a, $b): int => $comparator($b, $a);
	}

	/**
	 * String length comparator (ascending).
	 * 
	 * Sorts strings by length, shorter first.
	 * 
	 * @return callable(string, string): int
	 * 
	 * @example
	 * ```php
	 * $list = new SortedLinkedList(Comparators::byLength());
	 * $list->addAll(['hello', 'hi', 'world']);
	 * // Result: ['hi', 'hello', 'world']
	 * ```
	 */
	public static function byLength(): callable
	{
		return static fn(string $a, string $b): int => strlen($a) <=> strlen($b);
	}
}

