<?php
declare(strict_types=1);

require 'vendor/autoload.php';

use SortedList\SortedLinkedList;
use SortedList\Comparators;

echo "=== SortedLinkedList Comprehensive Usage Examples ===\n\n";

// Basic Usage - Integer Lists
echo "1. Basic Integer Usage:\n";
$numbers = new SortedLinkedList();
$numbers->addAll([5, 1, 9, 3, 7, 2]);
echo "Added [5, 1, 9, 3, 7, 2], result: " . implode(', ', $numbers->toArray()) . "\n";
echo "Count: " . count($numbers) . "\n";
echo "First: " . $numbers->first() . ", Last: " . $numbers->last() . "\n";
echo "Contains 3: " . ($numbers->contains(3) ? 'yes' : 'no') . "\n";
echo "Contains 6: " . ($numbers->contains(6) ? 'yes' : 'no') . "\n\n";

// String Usage with Different Comparators
echo "2. String Usage with Custom Comparators:\n";

// Case-sensitive (default)
$strings = new SortedLinkedList();
$strings->addAll(['banana', 'Apple', 'cherry', 'date']);
echo "Default (case-sensitive): " . implode(', ', $strings->toArray()) . "\n";

// Case-insensitive
$ciStrings = new SortedLinkedList(Comparators::ciAsc());
$ciStrings->addAll(['banana', 'Apple', 'cherry', 'date']);
echo "Case-insensitive ascending: " . implode(', ', $ciStrings->toArray()) . "\n";

// Natural ordering for version-like strings
$versions = new SortedLinkedList(Comparators::natural());
$versions->addAll(['v1.10', 'v1.2', 'v1.1', 'v2.0']);
echo "Natural ordering: " . implode(', ', $versions->toArray()) . "\n";

// By string length
$byLength = new SortedLinkedList(Comparators::byLength());
$byLength->addAll(['a', 'hello', 'hi', 'world']);
echo "By length: " . implode(', ', $byLength->toArray()) . "\n\n";

// Fluent Interface
echo "3. Fluent Interface (Method Chaining):\n";
$fluent = (new SortedLinkedList())
    ->with(5)
    ->with(1)
    ->withAll([3, 9, 2])
    ->without(9)
    ->with(7);
echo "Fluent result: " . implode(', ', $fluent->toArray()) . "\n\n";

// Advanced Operations
echo "4. Advanced Operations:\n";
$advanced = new SortedLinkedList();
$advanced->addAll([1, 2, 2, 3, 2, 4, 2, 5]);
echo "Original: " . implode(', ', $advanced->toArray()) . "\n";

echo "Index of 2: " . $advanced->indexOf(2) . "\n";
echo "Remove first 2: " . ($advanced->remove(2) ? 'success' : 'not found') . "\n";
echo "After removing one 2: " . implode(', ', $advanced->toArray()) . "\n";

$removedCount = $advanced->removeAll(2);
echo "Removed {$removedCount} occurrences of 2: " . implode(', ', $advanced->toArray()) . "\n";

// Stack-like operations
$min = $advanced->popFirst();
$max = $advanced->popLast();
echo "Popped min ({$min}) and max ({$max}), remaining: " . implode(', ', $advanced->toArray()) . "\n\n";

// Type Safety
echo "5. Type Safety:\n";
$typeSafe = new SortedLinkedList();
$typeSafe->add(42);
echo "Type locked to: " . $typeSafe->getLockedType() . "\n";

try {
    $typeSafe->add("string");
    echo "This should not print\n";
} catch (TypeError $e) {
    echo "Type error caught: " . $e->getMessage() . "\n";
}

// Clearing resets type lock
$typeSafe->clear();
echo "After clear, type lock: " . ($typeSafe->getLockedType() ?? 'null') . "\n";
$typeSafe->add("now a string");
echo "New type lock: " . $typeSafe->getLockedType() . "\n\n";

// Iteration
echo "6. Iteration:\n";
$iterable = new SortedLinkedList();
$iterable->addAll([3, 1, 4, 1, 5]);

echo "Foreach: ";
foreach ($iterable as $value) {
    echo $value . ' ';
}
echo "\n";

echo "Iterator methods:\n";
$iterCopy = $iterable->copy(); // Don't empty the original for demo
while (!$iterCopy->isEmpty()) {
    echo "Pop: " . $iterCopy->popFirst() . " ";
}
echo "(copy now empty)\n\n";

// JSON Serialization
echo "7. JSON Serialization:\n";
$json = new SortedLinkedList();
$json->addAll([3, 1, 4]);
echo "JSON: " . json_encode($json) . "\n\n";

// Copying and Slicing
echo "8. Copying and Slicing:\n";
$original = new SortedLinkedList();
$original->addAll([1, 2, 3, 4, 5, 6, 7, 8, 9]);

$copy = $original->copy();
echo "Original: " . implode(', ', $original->toArray()) . "\n";
echo "Copy: " . implode(', ', $copy->toArray()) . "\n";

$slice = $original->slice(2, 3);
echo "Slice (index 2, length 3): " . implode(', ', $slice->toArray()) . "\n\n";

// Custom Comparator Example
echo "9. Custom Comparators:\n";

// Descending integers
$descending = new SortedLinkedList(Comparators::intDesc());
$descending->addAll([1, 5, 3, 9, 2]);
echo "Descending integers: " . implode(', ', $descending->toArray()) . "\n";

// Reverse any comparator
$reverseNatural = new SortedLinkedList(Comparators::reverse(Comparators::natural()));
$reverseNatural->addAll(['file1.txt', 'file10.txt', 'file2.txt']);
echo "Reverse natural: " . implode(', ', $reverseNatural->toArray()) . "\n";

// Custom lambda
$byLastChar = new SortedLinkedList(fn($a, $b) => substr($a, -1) <=> substr($b, -1));
$byLastChar->addAll(['banana', 'apple', 'cherry', 'date']);
echo "Sort by last character: " . implode(', ', $byLastChar->toArray()) . "\n\n";

// Performance Characteristics
echo "10. Performance Demo (Large Dataset):\n";
$large = new SortedLinkedList();
$data = range(1, 1000); // Reduced for demo
shuffle($data);

$start = microtime(true);
$large->addAll($data);
$addTime = microtime(true) - $start;

$start = microtime(true);
$found = $large->contains(500);
$searchTime = microtime(true) - $start;

$start = microtime(true);
$large->remove(750);
$removeTime = microtime(true) - $start;

echo "Added 1,000 shuffled items in " . round($addTime * 1000, 2) . "ms\n";
echo "Search for item: " . round($searchTime * 1000, 4) . "ms (found: " . ($found ? 'yes' : 'no') . ")\n";
echo "Remove item: " . round($removeTime * 1000, 4) . "ms\n";
echo "Final count: " . count($large) . "\n";

echo "\n=== All examples completed successfully! ===\n";