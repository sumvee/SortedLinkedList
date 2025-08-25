<?php
declare(strict_types=1);

namespace SortedList;

/**
 * A sorted linked list that maintains elements in ascending order.
 * 
 * This data structure automatically keeps elements sorted upon insertion.
 * It supports either integers or strings, but not both in the same instance.
 * The type is locked after the first element is added.
 * 
 * Features:
 * - O(1) insertion at head/tail when elements naturally go there
 * - O(n) insertion in general case (but with early termination)
 * - O(n) removal with early termination due to sorted nature
 * - O(n) search with early termination
 * - Custom comparators supported
 * - Implements standard PHP interfaces (Countable, IteratorAggregate, JsonSerializable)
 * 
 * @template T of int|string
 * @implements \IteratorAggregate<int, T>
 * 
 * @example
 * ```php
 * $list = new SortedLinkedList();
 * $list->addAll([5, 1, 3, 2, 4]);
 * echo implode(',', $list->toArray()); // "1,2,3,4,5"
 * 
 * $strings = new SortedLinkedList(Comparators::ciAsc());
 * $strings->addAll(['banana', 'Apple', 'cherry']);
 * // Results in: ['Apple', 'banana', 'cherry']
 * ```
 */
final class SortedLinkedList implements \Countable, \IteratorAggregate, \JsonSerializable
{

	/** @var null|'int'|'string' */
	private ?string $lockedType = null;

	/** @var null|callable(T,T):int */
	private $comparator;

	/** @var ?Node<T> */
	private ?Node $head = null;
	/** @var ?Node<T> */
	private ?Node $tail = null;
	private int $size = 0;

	/**
	 * Create a new sorted linked list.
	 * 
	 * @param null|callable(T,T):int $comparator Custom comparator function.
	 *                                          If null, uses natural ordering:
	 *                                          - Integers: ascending numeric order  
	 *                                          - Strings: lexicographic (strcmp)
	 * 
	 * @example
	 * ```php
	 * $list = new SortedLinkedList(); // Natural ordering
	 * $custom = new SortedLinkedList(fn($a, $b) => $b <=> $a); // Descending
	 * ```
	 */
	public function __construct(?callable $comparator = null)
	{
		$this->comparator = $comparator;
	}

	/**
	 * Add a single value to the list, maintaining sort order.
	 * 
	 * The value type (int or string) is locked after the first insertion.
	 * Subsequent additions must be of the same type.
	 * 
	 * Time Complexity:
	 * - O(1) if value belongs at head or tail
	 * - O(n) worst case for middle insertion
	 * 
	 * @param T $value The value to add (int or string)
	 * 
	 * @throws \TypeError If value type doesn't match the locked type
	 * 
	 * @example
	 * ```php
	 * $list = new SortedLinkedList();
	 * $list->add(5);
	 * $list->add(1); // List now contains [1, 5]
	 * ```
	 */
	public function add(int|string $value): void
	{
		$this->assertTypeOrLock($value);
		$node = new Node($value);


		if ($this->head === null) {
			$this->head = $this->tail = $node;
			$this->size = 1;
			return;
		}

		// Fast checks: prepend or append
		if ($this->compare($value, $this->head->value) <= 0) {
			$node->next = $this->head;
			$this->head->prev = $node;
			$this->head = $node;
			$this->size++;
			return;
		}
		if ($this->tail !== null && $this->compare($value, $this->tail->value) >= 0) {
			$node->prev = $this->tail;
			$this->tail->next = $node;
			$this->tail = $node;
			$this->size++;
			return;
		}

		// Walk to insertion point
		$curr = $this->head;
		while ($curr !== null && $this->compare($value, $curr->value) > 0) {
			$curr = $curr->next;
		}

		// Insert before $curr (we know $curr is not null due to while condition)
		assert($curr !== null);
		$prev = $curr->prev;
		$node->next = $curr;
		$node->prev = $prev;
		if ($prev !== null) {
			$prev->next = $node;
		}
		$curr->prev = $node;
		$this->size++;
	}

	/**
	 * Add multiple values to the list, maintaining sort order.
	 * 
	 * @param iterable<T> $values Collection of values to add
	 * 
	 * @throws \TypeError If any value type doesn't match the locked type
	 * 
	 * @example
	 * ```php
	 * $list = new SortedLinkedList();
	 * $list->addAll([5, 1, 3, 2]); // Results in [1, 2, 3, 5]
	 * ```
	 */
	public function addAll(iterable $values): void
	{
		foreach ($values as $v) {
			$this->add($v);
		}
	}

	/**
	 * Remove the first occurrence of a value from the list.
	 * 
	 * Due to the sorted nature, this method can exit early when
	 * it determines the value cannot exist further in the list.
	 * 
	 * @param T $value The value to remove
	 * 
	 * @return bool True if value was found and removed, false otherwise
	 * 
	 * @throws \TypeError If value type doesn't match the locked type
	 * 
	 * @example
	 * ```php
	 * $list->addAll([1, 2, 3]);
	 * $list->remove(2); // Returns true, list now [1, 3]
	 * $list->remove(5); // Returns false, value not found
	 * ```
	 */
	public function remove(int|string $value): bool
	{
		if ($this->head === null) {
			return false;
		}
		$this->assertTypeCompatible($value);

		// Because the list is sorted, we may early-exit.
		$curr = $this->head;
		while ($curr !== null) {
			$cmp = $this->compare($value, $curr->value);
			if ($cmp === 0) {
				$this->unlink($curr);
				return true;
			}
			if ($cmp < 0) {
				return false; // not found (we already passed where it would be)
			}
			$curr = $curr->next;
		}
		return false;
	}

	/**
	 * Check if the list contains a specific value.
	 * 
	 * Uses early termination optimization due to sorted nature.
	 * 
	 * @param T $value The value to search for
	 * 
	 * @return bool True if value exists, false otherwise
	 * 
	 * @throws \TypeError If value type doesn't match the locked type
	 * 
	 * @example
	 * ```php
	 * $list->addAll([1, 3, 5]);
	 * $list->contains(3); // Returns true
	 * $list->contains(4); // Returns false
	 * ```
	 */
	public function contains(int|string $value): bool
	{
		if ($this->head === null) {
			return false;
		}
		$this->assertTypeCompatible($value);
		$curr = $this->head;
		while ($curr !== null) {
			$cmp = $this->compare($value, $curr->value);
			if ($cmp === 0) {
				return true;
			}
			if ($cmp < 0) {
				return false;
			}
			$curr = $curr->next;
		}
		return false;
	}

	/**
	 * Get the first (smallest) element without removing it.
	 * 
	 * @return T The smallest element in the list
	 * 
	 * @throws \UnderflowException If the list is empty
	 * 
	 * @example
	 * ```php
	 * $list->addAll([3, 1, 4]);
	 * echo $list->first(); // Outputs: 1
	 * ```
	 */
	public function first(): int|string
	{
		if ($this->head === null) {
			throw new \UnderflowException('List is empty.');
		}
		return $this->head->value;
	}

	/**
	 * Get the last (largest) element without removing it.
	 * 
	 * @return T The largest element in the list
	 * 
	 * @throws \UnderflowException If the list is empty
	 * 
	 * @example
	 * ```php
	 * $list->addAll([3, 1, 4]);
	 * echo $list->last(); // Outputs: 4
	 * ```
	 */
	public function last(): int|string
	{
		if ($this->tail === null) {
			throw new \UnderflowException('List is empty.');
		}
		return $this->tail->value;
	}

	/**
	 * Remove and return the first (smallest) element.
	 * 
	 * @return T The smallest element that was removed
	 * 
	 * @throws \UnderflowException If the list is empty
	 * 
	 * @example
	 * ```php
	 * $list->addAll([3, 1, 4]);
	 * $min = $list->popFirst(); // Returns 1, list now [3, 4]
	 * ```
	 */
	public function popFirst(): int|string
	{
		if ($this->head === null) {
			throw new \UnderflowException('List is empty.');
		}
		$value = $this->head->value;
		$this->unlink($this->head);
		return $value;
	}

	/**
	 * Remove and return the last (largest) element.
	 * 
	 * @return T The largest element that was removed
	 * 
	 * @throws \UnderflowException If the list is empty
	 * 
	 * @example
	 * ```php
	 * $list->addAll([3, 1, 4]);
	 * $max = $list->popLast(); // Returns 4, list now [1, 3]
	 * ```
	 */
	public function popLast(): int|string
	{
		if ($this->tail === null) {
			throw new \UnderflowException('List is empty.');
		}
		$value = $this->tail->value;
		$this->unlink($this->tail);
		return $value;
	}

	/**
	 * Remove all elements from the list.
	 * 
	 * This also resets the type lock, allowing a different type
	 * to be used after clearing.
	 * 
	 * @example
	 * ```php
	 * $list->addAll([1, 2, 3]);
	 * $list->clear();
	 * $list->add('string'); // Now allows strings
	 * ```
	 */
	public function clear(): void
	{
		$this->head = $this->tail = null;
		$this->size = 0;
		$this->lockedType = null; // allow switching type after full clear
	}

	/**
	 * Convert the list to a standard PHP array.
	 * 
	 * Elements are returned in sorted order.
	 * 
	 * @return list<T> Array containing all elements in sorted order
	 * 
	 * @example
	 * ```php
	 * $list->addAll([3, 1, 4]);
	 * $array = $list->toArray(); // [1, 3, 4]
	 * ```
	 */
	public function toArray(): array
	{
		$out = [];
		for ($n = $this->head; $n !== null; $n = $n->next) {
			$out[] = $n->value;
		}
		return $out;
	}

	/**
	 * Get the number of elements in the list.
	 * 
	 * Implements the Countable interface, allowing use with count().
	 * 
	 * @return int The number of elements
	 * 
	 * @example
	 * ```php
	 * $list->addAll([1, 2, 3]);
	 * echo count($list); // Outputs: 3
	 * ```
	 */
	public function count(): int
	{
		return $this->size;
	}

	/**
	 * Get an iterator for the list elements.
	 * 
	 * Implements IteratorAggregate, allowing use in foreach loops.
	 * Elements are yielded in sorted order.
	 * 
	 * @return \Traversable<int, T> Iterator yielding elements in sorted order
	 * 
	 * @example
	 * ```php
	 * $list->addAll([3, 1, 4]);
	 * foreach ($list as $value) {
	 *     echo $value; // Outputs: 1, 3, 4
	 * }
	 * ```
	 */
	public function getIterator(): \Traversable
	{
		for ($n = $this->head; $n !== null; $n = $n->next) {
			yield $n->value;
		}
	}

	/**
	 * Check if the list is empty.
	 * 
	 * @return bool True if the list contains no elements
	 * 
	 * @example
	 * ```php
	 * $list = new SortedLinkedList();
	 * echo $list->isEmpty(); // true
	 * $list->add(1);
	 * echo $list->isEmpty(); // false
	 * ```
	 */
	public function isEmpty(): bool
	{
		return $this->size === 0;
	}

	/**
	 * Get the current locked type of the list.
	 * 
	 * @return null|'int'|'string' The locked type, or null if no type is locked
	 * 
	 * @example
	 * ```php
	 * $list = new SortedLinkedList();
	 * echo $list->getLockedType(); // null
	 * $list->add(42);
	 * echo $list->getLockedType(); // 'int'
	 * ```
	 */
	public function getLockedType(): ?string
	{
		return $this->lockedType;
	}

	/**
	 * Create a copy of the list with the same comparator.
	 * 
	 * @return self A new SortedLinkedList containing the same elements
	 * 
	 * @example
	 * ```php
	 * $original = new SortedLinkedList();
	 * $original->addAll([3, 1, 2]);
	 * $copy = $original->copy();
	 * // $copy contains [1, 2, 3] but is a separate instance
	 * ```
	 */
	public function copy(): self
	{
		$copy = new self($this->comparator);
		$copy->addAll($this->toArray());
		return $copy;
	}

	/**
	 * Remove all occurrences of a value (not just the first).
	 * 
	 * @param int|string $value The value to remove all occurrences of
	 * 
	 * @return int Number of elements removed
	 * 
	 * @throws \TypeError If value type doesn't match the locked type
	 * 
	 * @example
	 * ```php
	 * $list->addAll([1, 2, 2, 3, 2]);
	 * $count = $list->removeAll(2); // Returns 3
	 * // List now contains [1, 3]
	 * ```
	 */
	public function removeAll(int|string $value): int
	{
		if ($this->head === null) {
			return 0;
		}
		$this->assertTypeCompatible($value);

		$removed = 0;
		$curr = $this->head;
		
		while ($curr !== null) {
			$cmp = $this->compare($value, $curr->value);
			if ($cmp === 0) {
				$next = $curr->next;
				$this->unlink($curr);
				$removed++;
				$curr = $next;
			} elseif ($cmp < 0) {
				break; // Early exit - won't find more matches
			} else {
				$curr = $curr->next;
			}
		}
		
		return $removed;
	}

	/**
	 * Find the index of the first occurrence of a value.
	 * 
	 * @param int|string $value The value to search for
	 * 
	 * @return int The zero-based index, or -1 if not found
	 * 
	 * @throws \TypeError If value type doesn't match the locked type
	 * 
	 * @example
	 * ```php
	 * $list->addAll([1, 3, 5, 7]);
	 * echo $list->indexOf(5); // 2
	 * echo $list->indexOf(4); // -1
	 * ```
	 */
	public function indexOf(int|string $value): int
	{
		if ($this->head === null) {
			return -1;
		}
		$this->assertTypeCompatible($value);

		$index = 0;
		$curr = $this->head;
		
		while ($curr !== null) {
			$cmp = $this->compare($value, $curr->value);
			if ($cmp === 0) {
				return $index;
			}
			if ($cmp < 0) {
				return -1; // Early exit
			}
			$curr = $curr->next;
			$index++;
		}
		
		return -1;
	}

	/**
	 * Get a slice of the list as a new SortedLinkedList.
	 * 
	 * @param int $start Start index (inclusive)
	 * @param int|null $length Number of elements, or null for rest of list
	 * 
	 * @return self A new SortedLinkedList containing the slice
	 * 
	 * @example
	 * ```php
	 * $list->addAll([1, 2, 3, 4, 5]);
	 * $slice = $list->slice(1, 3); // Contains [2, 3, 4]
	 * ```
	 */
	public function slice(int $start, ?int $length = null): self
	{
		$array = $this->toArray();
		$sliced = array_slice($array, $start, $length);
		
		$result = new self($this->comparator);
		$result->addAll($sliced);
		return $result;
	}

	// Fluent Interface Methods
	// These methods return $this to enable method chaining

	/**
	 * Fluent version of add() - adds a value and returns the list for chaining.
	 * 
	 * @param int|string $value The value to add
	 * 
	 * @return self This list instance for method chaining
	 * 
	 * @throws \TypeError If value type doesn't match the locked type
	 * 
	 * @example
	 * ```php
	 * $list = (new SortedLinkedList())
	 *     ->with(5)
	 *     ->with(1)
	 *     ->with(3);
	 * // List now contains [1, 3, 5]
	 * ```
	 */
	public function with(int|string $value): self
	{
		$this->add($value);
		return $this;
	}

	/**
	 * Fluent version of addAll() - adds multiple values and returns the list.
	 * 
	 * @param iterable<int|string> $values Collection of values to add
	 * 
	 * @return self This list instance for method chaining
	 * 
	 * @throws \TypeError If any value type doesn't match the locked type
	 * 
	 * @example
	 * ```php
	 * $list = (new SortedLinkedList())
	 *     ->withAll([5, 1, 3])
	 *     ->with(2);
	 * // List now contains [1, 2, 3, 5]
	 * ```
	 */
	public function withAll(iterable $values): self
	{
		$this->addAll($values);
		return $this;
	}

	/**
	 * Fluent version of remove() - removes a value and returns the list.
	 * 
	 * @param int|string $value The value to remove
	 * 
	 * @return self This list instance for method chaining
	 * 
	 * @throws \TypeError If value type doesn't match the locked type
	 * 
	 * @example
	 * ```php
	 * $list->withAll([1, 2, 3, 2, 4])
	 *      ->without(2)  // Removes first occurrence
	 *      ->without(5); // No effect - value not found
	 * // List now contains [1, 2, 3, 4]
	 * ```
	 */
	public function without(int|string $value): self
	{
		$this->remove($value);
		return $this;
	}

	/**
	 * Fluent version of removeAll() - removes all occurrences and returns the list.
	 * 
	 * @param int|string $value The value to remove all occurrences of
	 * 
	 * @return self This list instance for method chaining
	 * 
	 * @throws \TypeError If value type doesn't match the locked type
	 * 
	 * @example
	 * ```php
	 * $list->withAll([1, 2, 3, 2, 4, 2])
	 *      ->withoutAll(2); // Removes all 2's
	 * // List now contains [1, 3, 4]
	 * ```
	 */
	public function withoutAll(int|string $value): self
	{
		$this->removeAll($value);
		return $this;
	}

	/**
	 * Fluent version of clear() - clears the list and returns it for chaining.
	 * 
	 * @return self This list instance for method chaining
	 * 
	 * @example
	 * ```php
	 * $list->withAll([1, 2, 3])
	 *      ->cleared()
	 *      ->with('string'); // Can now add strings after clearing
	 * ```
	 */
	public function cleared(): self
	{
		$this->clear();
		return $this;
	}

	/**
	 * Prepare the list for JSON serialization.
	 * 
	 * Implements JsonSerializable, allowing use with json_encode().
	 * 
	 * @return mixed Array representation for JSON encoding
	 * 
	 * @example
	 * ```php
	 * $list->addAll([3, 1, 4]);
	 * echo json_encode($list); // Outputs: [1,3,4]
	 * ```
	 */
	public function jsonSerialize(): mixed
	{
		return $this->toArray();
	}

	/** 
	 * @internal 
	 * @param Node<T> $node
	 */
	private function unlink(Node $node): void
	{
		$prev = $node->prev;
		$next = $node->next;

		if ($prev === null) {
			$this->head = $next;
		} else {
			$prev->next = $next;
		}

		if ($next === null) {
			$this->tail = $prev;
		} else {
			$next->prev = $prev;
		}

		$node->prev = $node->next = null;
		$this->size--;

		if ($this->size === 0) {
			$this->lockedType = null;
		}
	}

	/**
	 * @param T $a
	 * @param T $b
	 */
	private function compare(int|string $a, int|string $b): int
	{
		if ($this->comparator !== null) {
			return ($this->comparator)($a, $b);
		}

		// Default ascending
		if (is_int($a) && is_int($b)) {
			return $a <=> $b;           // integers
		}
		// strings (binary-safe, locale-independent)
		return strcmp((string)$a, (string)$b);
	}

	/**
	 * @param T $value
	 */
	private function assertTypeOrLock(int|string $value): void
	{
		$t = gettype($value);
		if ($this->lockedType === null) {
			$this->lockedType = $t === 'integer' ? 'int' : 'string';
			return;
		}
		$this->assertTypeCompatible($value);
	}

	/**
	 * @param int|string $value
	 */
	private function assertTypeCompatible(int|string $value): void
	{
		$t = gettype($value) === 'integer' ? 'int' : 'string';
		if ($this->lockedType !== null && $t !== $this->lockedType) {
			throw new \TypeError(sprintf(
				'Mismatched value type: list holds %s, got %s.',
				$this->lockedType,
				$t
			));
		}
	}
}


