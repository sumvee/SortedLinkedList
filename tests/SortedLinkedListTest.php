<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use SortedList\SortedLinkedList;
use SortedList\Comparators;

final class SortedLinkedListTest extends TestCase
{
	// ========== Basic Functionality Tests ==========
	
	/**
	 * Tests basic integer sorting functionality with natural ascending order.
	 * Verifies core operations: add, contains, first, last.
	 */
	public function testIntAscending(): void
	{
		$l = new SortedLinkedList();
		$l->addAll([5, 1, 4, 2, 3]);
		$this->assertSame([1,2,3,4,5], $l->toArray());
		$this->assertTrue($l->contains(3));
		$this->assertFalse($l->contains(9));
		$this->assertSame(1, $l->first());
		$this->assertSame(5, $l->last());
	}

	/**
	 * Tests behavior of empty list operations and initial state.
	 */
	public function testEmptyList(): void
	{
		$l = new SortedLinkedList();
		$this->assertTrue($l->isEmpty());
		$this->assertSame(0, $l->count());
		$this->assertSame([], $l->toArray());
		$this->assertNull($l->getLockedType());
	}

	public function testSingleElement(): void
	{
		$l = new SortedLinkedList();
		$l->add(42);
		$this->assertFalse($l->isEmpty());
		$this->assertSame(1, $l->count());
		$this->assertSame([42], $l->toArray());
		$this->assertSame(42, $l->first());
		$this->assertSame(42, $l->last());
		$this->assertSame('int', $l->getLockedType());
	}

	// ========== String Tests ==========

	public function testStringsCaseInsensitive(): void
	{
		$l = new SortedLinkedList(Comparators::ciAsc());
		$l->addAll(['b', 'A', 'c']);
		$this->assertSame(['A','b','c'], $l->toArray());
	}

	public function testStringsDefault(): void
	{
		$l = new SortedLinkedList();
		$l->addAll(['banana', 'Apple', 'cherry', 'date']);
		$this->assertSame(['Apple', 'banana', 'cherry', 'date'], $l->toArray());
		$this->assertSame('string', $l->getLockedType());
	}

	// ========== Removal Tests ==========

	public function testRemoveEarlyExit(): void
	{
		$l = new SortedLinkedList();
		$l->addAll([1,2,4,5]);
		$this->assertFalse($l->remove(3));
		$this->assertTrue($l->remove(4));
		$this->assertSame([1,2,5], $l->toArray());
	}

	public function testRemoveFirst(): void
	{
		$l = new SortedLinkedList();
		$l->addAll([1, 2, 3]);
		$this->assertTrue($l->remove(1));
		$this->assertSame([2, 3], $l->toArray());
	}

	public function testRemoveLast(): void
	{
		$l = new SortedLinkedList();
		$l->addAll([1, 2, 3]);
		$this->assertTrue($l->remove(3));
		$this->assertSame([1, 2], $l->toArray());
	}

	public function testRemoveMiddle(): void
	{
		$l = new SortedLinkedList();
		$l->addAll([1, 2, 3, 4, 5]);
		$this->assertTrue($l->remove(3));
		$this->assertSame([1, 2, 4, 5], $l->toArray());
	}

	public function testRemoveAll(): void
	{
		$l = new SortedLinkedList();
		$l->addAll([1, 2, 2, 3, 2, 4, 2, 5]);
		$removed = $l->removeAll(2);
		$this->assertSame(4, $removed);
		$this->assertSame([1, 3, 4, 5], $l->toArray());
	}

	public function testRemoveAllNonExistent(): void
	{
		$l = new SortedLinkedList();
		$l->addAll([1, 3, 5]);
		$removed = $l->removeAll(2);
		$this->assertSame(0, $removed);
		$this->assertSame([1, 3, 5], $l->toArray());
	}

	// ========== Pop Tests ==========

	public function testPopAndUnderflow(): void
	{
		$l = new SortedLinkedList();
		$l->addAll([2,1]);
		$this->assertSame(1, $l->popFirst());
		$this->assertSame(2, $l->popLast());
		$this->expectException(UnderflowException::class);
		$l->popFirst();
	}

	public function testPopLastUnderflow(): void
	{
		$l = new SortedLinkedList();
		$this->expectException(UnderflowException::class);
		$l->popLast();
	}

	public function testFirstLastUnderflow(): void
	{
		$l = new SortedLinkedList();
		$this->expectException(UnderflowException::class);
		$l->first();
	}

	public function testLastUnderflow(): void
	{
		$l = new SortedLinkedList();
		$this->expectException(UnderflowException::class);
		$l->last();
	}

	// ========== Type Safety Tests ==========

	/**
	 * Tests type locking mechanism - once int is added, strings should be rejected.
	 */
	public function testTypeLocking(): void
	{
		$l = new SortedLinkedList();
		$l->add(42);
		$this->expectException(TypeError::class);
		$l->add("nope");
	}

	public function testTypeLockingString(): void
	{
		$l = new SortedLinkedList();
		$l->add("hello");
		$this->expectException(TypeError::class);
		$l->add(42);
	}

	public function testTypeLockingContains(): void
	{
		$l = new SortedLinkedList();
		$l->add(1);
		$this->expectException(TypeError::class);
		$l->contains("string");
	}

	public function testTypeLockingRemove(): void
	{
		$l = new SortedLinkedList();
		$l->add(1);
		$this->expectException(TypeError::class);
		$l->remove("string");
	}

	public function testTypeLockingIndexOf(): void
	{
		$l = new SortedLinkedList();
		$l->add(1);
		$this->expectException(TypeError::class);
		$l->indexOf("string");
	}

	public function testClearResetsType(): void
	{
		$l = new SortedLinkedList();
		$l->add(1);
		$l->clear();
		$l->add("ok");
		$this->assertSame(['ok'], $l->toArray());
	}

	// ========== Search and Index Tests ==========

	public function testContainsEarlyExit(): void
	{
		$l = new SortedLinkedList();
		$l->addAll([1, 3, 5, 7, 9]);
		$this->assertTrue($l->contains(5));
		$this->assertFalse($l->contains(6)); // Should exit early
		$this->assertFalse($l->contains(0)); // Before first
		$this->assertFalse($l->contains(10)); // After last
	}

	public function testIndexOf(): void
	{
		$l = new SortedLinkedList();
		$l->addAll([10, 30, 50, 70]);
		$this->assertSame(0, $l->indexOf(10));
		$this->assertSame(1, $l->indexOf(30));
		$this->assertSame(2, $l->indexOf(50));
		$this->assertSame(3, $l->indexOf(70));
		$this->assertSame(-1, $l->indexOf(40)); // Not found
		$this->assertSame(-1, $l->indexOf(5));  // Before first
		$this->assertSame(-1, $l->indexOf(80)); // After last
	}

	public function testIndexOfEmpty(): void
	{
		$l = new SortedLinkedList();
		$this->assertSame(-1, $l->indexOf(1));
	}

	// ========== Insertion Edge Cases ==========

	public function testInsertHead(): void
	{
		$l = new SortedLinkedList();
		$l->addAll([5, 10, 15]);
		$l->add(1); // Should go to head
		$this->assertSame([1, 5, 10, 15], $l->toArray());
	}

	public function testInsertTail(): void
	{
		$l = new SortedLinkedList();
		$l->addAll([5, 10, 15]);
		$l->add(20); // Should go to tail
		$this->assertSame([5, 10, 15, 20], $l->toArray());
	}

	public function testInsertDuplicates(): void
	{
		$l = new SortedLinkedList();
		$l->addAll([1, 3, 3, 3, 5]);
		$this->assertSame([1, 3, 3, 3, 5], $l->toArray());
		$this->assertTrue($l->contains(3));
		$this->assertSame(1, $l->indexOf(3)); // First occurrence
	}

	// ========== Comparator Tests ==========

	public function testIntDesc(): void
	{
		$l = new SortedLinkedList(Comparators::intDesc());
		$l->addAll([1, 5, 3, 9, 2]);
		$this->assertSame([9, 5, 3, 2, 1], $l->toArray());
	}

	public function testNaturalOrdering(): void
	{
		$l = new SortedLinkedList(Comparators::natural());
		$l->addAll(['file10.txt', 'file2.txt', 'file1.txt']);
		$this->assertSame(['file1.txt', 'file2.txt', 'file10.txt'], $l->toArray());
	}

	public function testByLength(): void
	{
		$l = new SortedLinkedList(Comparators::byLength());
		$l->addAll(['hello', 'hi', 'world', 'a']);
		$this->assertSame(['a', 'hi', 'hello', 'world'], $l->toArray());
	}

	public function testReverseComparator(): void
	{
		$l = new SortedLinkedList(Comparators::reverse(Comparators::natural()));
		$l->addAll(['file1.txt', 'file10.txt', 'file2.txt']);
		$this->assertSame(['file10.txt', 'file2.txt', 'file1.txt'], $l->toArray());
	}

	public function testCustomComparator(): void
	{
		// Sort by last character
		$l = new SortedLinkedList(fn($a, $b) => substr($a, -1) <=> substr($b, -1));
		$l->addAll(['banana', 'apple', 'cherry']);
		$this->assertSame(['banana', 'apple', 'cherry'], $l->toArray());
	}

	// ========== Fluent Interface Tests ==========

	/**
	 * Tests fluent interface for method chaining with with() method.
	 */
	public function testFluentWith(): void
	{
		$l = (new SortedLinkedList())
			->with(5)
			->with(1)
			->with(3);
		$this->assertSame([1, 3, 5], $l->toArray());
	}

	public function testFluentWithAll(): void
	{
		$l = (new SortedLinkedList())
			->withAll([5, 1, 3])
			->with(2);
		$this->assertSame([1, 2, 3, 5], $l->toArray());
	}

	public function testFluentWithout(): void
	{
		$l = (new SortedLinkedList())
			->withAll([1, 2, 3, 4, 5])
			->without(3);
		$this->assertSame([1, 2, 4, 5], $l->toArray());
	}

	public function testFluentWithoutAll(): void
	{
		$l = (new SortedLinkedList())
			->withAll([1, 2, 2, 3, 2])
			->withoutAll(2);
		$this->assertSame([1, 3], $l->toArray());
	}

	public function testFluentCleared(): void
	{
		$l = (new SortedLinkedList())
			->with(1)
			->cleared()
			->with('string');
		$this->assertSame(['string'], $l->toArray());
	}

	// ========== Copying and Slicing Tests ==========

	public function testCopy(): void
	{
		$original = new SortedLinkedList();
		$original->addAll([3, 1, 2]);
		$copy = $original->copy();
		
		$this->assertSame([1, 2, 3], $copy->toArray());
		$this->assertEquals($original->toArray(), $copy->toArray());
		
		// Modify original, copy should be unaffected
		$original->add(4);
		$this->assertSame([1, 2, 3, 4], $original->toArray());
		$this->assertSame([1, 2, 3], $copy->toArray());
	}

	public function testCopyWithComparator(): void
	{
		$original = new SortedLinkedList(Comparators::intDesc());
		$original->addAll([1, 3, 2]);
		$copy = $original->copy();
		
		$this->assertSame([3, 2, 1], $copy->toArray());
		
		// New additions should use same comparator
		$copy->add(4);
		$this->assertSame([4, 3, 2, 1], $copy->toArray());
	}

	public function testSlice(): void
	{
		$l = new SortedLinkedList();
		$l->addAll([1, 2, 3, 4, 5, 6, 7]);
		
		$slice = $l->slice(2, 3);
		$this->assertSame([3, 4, 5], $slice->toArray());
		
		$sliceToEnd = $l->slice(4);
		$this->assertSame([5, 6, 7], $sliceToEnd->toArray());
		
		$emptySlice = $l->slice(10);
		$this->assertSame([], $emptySlice->toArray());
	}

	// ========== Interface Implementation Tests ==========

	public function testCountable(): void
	{
		$l = new SortedLinkedList();
		$this->assertSame(0, count($l));
		
		$l->addAll([1, 2, 3]);
		$this->assertSame(3, count($l));
		
		$l->remove(2);
		$this->assertSame(2, count($l));
	}

	public function testIterable(): void
	{
		$l = new SortedLinkedList();
		$l->addAll([3, 1, 4]);
		
		$values = [];
		foreach ($l as $value) {
			$values[] = $value;
		}
		
		$this->assertSame([1, 3, 4], $values);
	}

	public function testJsonSerializable(): void
	{
		$l = new SortedLinkedList();
		$l->addAll([3, 1, 4]);
		
		$json = json_encode($l);
		$this->assertSame('[1,3,4]', $json);
	}

	// ========== Edge Cases and Stress Tests ==========

	/**
	 * Tests handling of large dataset (1000 elements) to verify scalability.
	 */
	public function testLargeDataset(): void
	{
		$l = new SortedLinkedList();
		$data = range(1, 1000);
		shuffle($data);
		
		$l->addAll($data);
		$result = $l->toArray();
		
		$this->assertSame(range(1, 1000), $result);
		$this->assertSame(1000, count($l));
	}

	public function testEmptyOperations(): void
	{
		$l = new SortedLinkedList();
		
		$this->assertFalse($l->remove(1));
		$this->assertFalse($l->contains(1));
		$this->assertSame(-1, $l->indexOf(1));
		$this->assertSame(0, $l->removeAll(1));
		$this->assertSame([], $l->slice(0, 5)->toArray());
		$this->assertSame([], $l->copy()->toArray());
	}

	public function testAllSameValues(): void
	{
		$l = new SortedLinkedList();
		$l->addAll([5, 5, 5, 5, 5]);
		
		$this->assertSame([5, 5, 5, 5, 5], $l->toArray());
		$this->assertSame(0, $l->indexOf(5));
		$this->assertTrue($l->remove(5));
		$this->assertSame([5, 5, 5, 5], $l->toArray());
		
		$removed = $l->removeAll(5);
		$this->assertSame(4, $removed);
		$this->assertTrue($l->isEmpty());
	}

	// ========== Performance Characteristics ==========

	public function testHeadTailInsertion(): void
	{
		$l = new SortedLinkedList();
		
		// Test head insertion (O(1))
		$l->add(10);
		$l->add(1); // Should go to head
		$this->assertSame([1, 10], $l->toArray());
		
		// Test tail insertion (O(1))
		$l->add(20); // Should go to tail
		$this->assertSame([1, 10, 20], $l->toArray());
	}

	public function testClearBehavior(): void
	{
		$l = new SortedLinkedList();
		$l->addAll([1, 2, 3]);
		
		$this->assertSame(3, count($l));
		$this->assertSame('int', $l->getLockedType());
		
		$l->clear();
		
		$this->assertSame(0, count($l));
		$this->assertTrue($l->isEmpty());
		$this->assertNull($l->getLockedType());
		$this->assertSame([], $l->toArray());
	}

	// ========== Additional Comparator Tests ==========

	public function testCaseInsensitiveDesc(): void
	{
		$l = new SortedLinkedList(Comparators::ciDesc());
		$l->addAll(['banana', 'Apple', 'cherry']);
		$this->assertSame(['cherry', 'banana', 'Apple'], $l->toArray());
	}

	public function testNaturalCaseInsensitive(): void
	{
		$l = new SortedLinkedList(Comparators::naturalCi());
		$l->addAll(['File10.txt', 'file2.txt', 'FILE1.txt']);
		$this->assertSame(['FILE1.txt', 'file2.txt', 'File10.txt'], $l->toArray());
	}
}
