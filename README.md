# SortedLinkedList

A high-performance PHP library providing a sorted linked list data structure that maintains elements in ascending order automatically.

## Features

- **Automatic Sorting**: Elements are automatically kept in sorted order upon insertion
- **Type Safety**: Supports either integers or strings, but not both in the same instance (type-locked after first insertion)
- **Performance Optimized**: 
  - O(1) insertion at head/tail when elements naturally belong there
  - O(n) insertion with early termination for middle placements
  - O(n) search with early exit optimization
- **Custom Comparators**: Support for custom sorting logic
- **Standard PHP Interfaces**: Implements `Countable`, `IteratorAggregate`, `JsonSerializable`
- **Fluent Interface**: Method chaining support for cleaner code
- **Memory Efficient**: Proper cleanup and garbage collection support

## Installation

```bash
composer install
```

## Quick Start

```php
<?php
require 'vendor/autoload.php';

use SortedList\SortedLinkedList;
use SortedList\Comparators;

// Basic usage with integers
$numbers = new SortedLinkedList();
$numbers->addAll([5, 1, 9, 3, 7, 2]);
echo implode(', ', $numbers->toArray()); // Output: 1, 2, 3, 5, 7, 9

// String usage with custom comparator
$strings = new SortedLinkedList(Comparators::ciAsc());
$strings->addAll(['banana', 'Apple', 'cherry']);
print_r($strings->toArray()); // ['Apple', 'banana', 'cherry']

// Fluent interface
$list = (new SortedLinkedList())
    ->with(5)
    ->with(1)
    ->withAll([3, 9, 2])
    ->without(9);
echo implode(', ', $list->toArray()); // Output: 1, 2, 3, 5
```

## API Reference

### Core Methods

#### Construction
```php
$list = new SortedLinkedList(?callable $comparator = null);
```

#### Adding Elements
```php
$list->add(int|string $value): void                    // Add single element
$list->addAll(iterable $values): void                  // Add multiple elements
$list->with(int|string $value): self                   // Fluent add
$list->withAll(iterable $values): self                 // Fluent add multiple
```

#### Removing Elements
```php
$list->remove(int|string $value): bool                 // Remove first occurrence
$list->removeAll(int|string $value): int               // Remove all occurrences
$list->without(int|string $value): self                // Fluent remove
$list->withoutAll(int|string $value): self             // Fluent remove all
$list->clear(): void                                    // Remove all elements
```

#### Accessing Elements
```php
$list->first(): int|string                             // Get first (smallest) element
$list->last(): int|string                              // Get last (largest) element
$list->popFirst(): int|string                          // Remove and return first
$list->popLast(): int|string                           // Remove and return last
```

#### Searching
```php
$list->contains(int|string $value): bool               // Check if value exists
$list->indexOf(int|string $value): int                 // Get index (-1 if not found)
```

#### Utility Methods
```php
$list->isEmpty(): bool                                  // Check if empty
$list->count(): int                                     // Get element count
$list->getLockedType(): ?string                        // Get type lock ('int'|'string'|null)
$list->toArray(): array                                // Convert to PHP array
$list->copy(): self                                     // Create copy with same comparator
$list->slice(int $start, ?int $length = null): self    // Get slice as new list
```

### Built-in Comparators

```php
use SortedList\Comparators;

// String comparators
Comparators::ciAsc()          // Case-insensitive ascending
Comparators::ciDesc()         // Case-insensitive descending
Comparators::natural()        // Natural order (good for versions: v1.1, v1.2, v1.10)
Comparators::naturalCi()      // Natural order, case-insensitive
Comparators::byLength()       // Sort by string length

// Integer comparators
Comparators::intAsc()         // Integer ascending (default behavior)
Comparators::intDesc()        // Integer descending

// Utility
Comparators::reverse($comparator)  // Reverse any comparator
```

## Advanced Usage

### Custom Comparators

```php
// Sort by string length, then alphabetically
$comparator = function($a, $b) {
    $lengthDiff = strlen($a) - strlen($b);
    return $lengthDiff !== 0 ? $lengthDiff : strcmp($a, $b);
};

$list = new SortedLinkedList($comparator);
$list->addAll(['hello', 'hi', 'world', 'a']);
// Result: ['a', 'hi', 'hello', 'world']
```

### Working with Large Datasets

```php
$list = new SortedLinkedList();

// Efficiently add many elements
$data = range(1, 10000);
shuffle($data);
$list->addAll($data); // Automatically sorted

// Fast head/tail operations (O(1))
$list->add(0);      // Goes to head - O(1)
$list->add(10001);  // Goes to tail - O(1)
```

### JSON Serialization

```php
$list = new SortedLinkedList();
$list->addAll([3, 1, 4, 1, 5]);

echo json_encode($list); // [1,1,3,4,5]
```

### Iteration

```php
$list = new SortedLinkedList();
$list->addAll([3, 1, 4]);

// Standard foreach
foreach ($list as $value) {
    echo $value . ' '; // 1 3 4
}

// Using count()
echo count($list); // 3
```

## Type Safety

The list locks to the first type added and rejects mixed types:

```php
$list = new SortedLinkedList();
$list->add(42);           // Locks to 'int'
$list->add("string");     // Throws TypeError

echo $list->getLockedType(); // 'int'

$list->clear();           // Resets type lock
$list->add("now works");  // Now accepts strings
```

## Performance Characteristics

- **Head/Tail Insertion**: O(1) when elements naturally belong at ends
- **Middle Insertion**: O(n) with early termination
- **Search Operations**: O(n) with early exit when value cannot exist
- **Removal**: O(n) with early termination due to sorted nature

## Testing

Run the comprehensive test suite:

```bash
# Run all tests
./vendor/bin/phpunit tests/

# Run with detailed output
./vendor/bin/phpunit tests/ --testdox

# Run specific test categories
./vendor/bin/phpunit tests/SortedLinkedListTest.php  # Core functionality
./vendor/bin/phpunit tests/ComparatorsTest.php       # Comparator tests
./vendor/bin/phpunit tests/SortedLinkedListPerformanceTest.php  # Performance tests
```

## Requirements

- PHP 8.0 or higher
- PHPUnit 10.5+ (for development/testing)
- PHPStan (for static analysis)

## License

This project is open source. See LICENSE file for details.

## Contributing

1. Run tests: `./vendor/bin/phpunit tests/`
2. Check types: `./vendor/bin/phpstan analyse src --level=8`
3. Follow existing code style and patterns
4. Add tests for new functionality

## Examples

See `useage.php` for comprehensive usage examples demonstrating all features and capabilities.