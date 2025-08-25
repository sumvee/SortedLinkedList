<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use SortedList\Comparators;

final class ComparatorsTest extends TestCase
{
    // ========== Case-Insensitive Comparators ==========

    public function testCiAsc(): void
    {
        $comparator = Comparators::ciAsc();
        
        $this->assertSame(0, $comparator('apple', 'APPLE'));
        $this->assertLessThan(0, $comparator('apple', 'banana'));
        $this->assertGreaterThan(0, $comparator('cherry', 'banana'));
    }

    public function testCiDesc(): void
    {
        $comparator = Comparators::ciDesc();
        
        $this->assertSame(0, $comparator('apple', 'APPLE'));
        $this->assertGreaterThan(0, $comparator('apple', 'banana'));
        $this->assertLessThan(0, $comparator('cherry', 'banana'));
    }

    // ========== Integer Comparators ==========

    public function testIntAsc(): void
    {
        $comparator = Comparators::intAsc();
        
        $this->assertSame(0, $comparator(5, 5));
        $this->assertLessThan(0, $comparator(3, 7));
        $this->assertGreaterThan(0, $comparator(9, 2));
    }

    public function testIntDesc(): void
    {
        $comparator = Comparators::intDesc();
        
        $this->assertSame(0, $comparator(5, 5));
        $this->assertGreaterThan(0, $comparator(3, 7));
        $this->assertLessThan(0, $comparator(9, 2));
    }

    // ========== Natural Order Comparators ==========

    public function testNatural(): void
    {
        $comparator = Comparators::natural();
        
        // Test natural ordering with numbers in strings
        $this->assertLessThan(0, $comparator('file1.txt', 'file2.txt'));
        $this->assertLessThan(0, $comparator('file2.txt', 'file10.txt'));
        $this->assertLessThan(0, $comparator('file9.txt', 'file10.txt'));
        
        // Regular string comparison would put 'file10.txt' before 'file2.txt'
        $this->assertSame(0, $comparator('same', 'same'));
    }

    public function testNaturalCi(): void
    {
        $comparator = Comparators::naturalCi();
        
        // Case insensitive natural ordering
        $this->assertLessThan(0, $comparator('FILE1.txt', 'file2.txt'));
        $this->assertLessThan(0, $comparator('File2.txt', 'FILE10.txt'));
        $this->assertSame(0, $comparator('FILE.txt', 'file.txt'));
    }

    // ========== String Length Comparator ==========

    public function testByLength(): void
    {
        $comparator = Comparators::byLength();
        
        $this->assertSame(0, $comparator('hello', 'world')); // Same length
        $this->assertLessThan(0, $comparator('hi', 'hello')); // 2 < 5
        $this->assertGreaterThan(0, $comparator('longer', 'short')); // 6 > 5
        $this->assertLessThan(0, $comparator('a', 'bb')); // 1 < 2
    }

    // ========== Reverse Comparator ==========

    public function testReverse(): void
    {
        $naturalComparator = Comparators::natural();
        $reverseNaturalComparator = Comparators::reverse($naturalComparator);
        
        // Original: file1.txt < file2.txt
        $this->assertLessThan(0, $naturalComparator('file1.txt', 'file2.txt'));
        
        // Reversed: file1.txt > file2.txt
        $this->assertGreaterThan(0, $reverseNaturalComparator('file1.txt', 'file2.txt'));
        
        // Equal values should remain equal
        $this->assertSame(0, $reverseNaturalComparator('same', 'same'));
    }

    public function testReverseWithIntDesc(): void
    {
        $intDesc = Comparators::intDesc();
        $reverseIntDesc = Comparators::reverse($intDesc);
        
        // intDesc: 5 comes before 3 in descending order, so comparison should be negative  
        $this->assertLessThan(0, $intDesc(5, 3));
        
        // reverse(intDesc): reverse should flip the sign, making it positive
        $this->assertGreaterThan(0, $reverseIntDesc(5, 3));
    }

    public function testReverseWithCustomComparator(): void
    {
        // Custom: sort by string length
        $byLength = fn($a, $b) => strlen($a) <=> strlen($b);
        $reverseByLength = Comparators::reverse($byLength);
        
        // Original: shorter strings first
        $this->assertLessThan(0, $byLength('hi', 'hello'));
        
        // Reversed: longer strings first
        $this->assertGreaterThan(0, $reverseByLength('hi', 'hello'));
    }

    // ========== Integration Tests ==========

    public function testComparatorsWithArray(): void
    {
        // Test that comparators work correctly with usort
        $data = ['file10.txt', 'file2.txt', 'file1.txt', 'file20.txt'];
        
        // Natural ordering
        $naturalSorted = $data;
        usort($naturalSorted, Comparators::natural());
        $this->assertSame(['file1.txt', 'file2.txt', 'file10.txt', 'file20.txt'], $naturalSorted);
        
        // Reverse natural ordering
        $reverseNaturalSorted = $data;
        usort($reverseNaturalSorted, Comparators::reverse(Comparators::natural()));
        $this->assertSame(['file20.txt', 'file10.txt', 'file2.txt', 'file1.txt'], $reverseNaturalSorted);
    }

    public function testComparatorsConsistency(): void
    {
        // Test that comparators are consistent (if a < b, then b > a)
        $comparators = [
            'ciAsc' => Comparators::ciAsc(),
            'ciDesc' => Comparators::ciDesc(),
            'natural' => Comparators::natural(),
            'naturalCi' => Comparators::naturalCi(),
            'byLength' => Comparators::byLength(),
        ];
        
        foreach ($comparators as $name => $comparator) {
            if (in_array($name, ['byLength'])) {
                $a = 'short';
                $b = 'longer';
            } else {
                $a = 'apple';
                $b = 'banana';
            }
            
            $result1 = $comparator($a, $b);
            $result2 = $comparator($b, $a);
            
            // Results should be opposite (or both zero for equal values)
            $this->assertSame($result1 === 0, $result2 === 0, "Comparator {$name} inconsistent for equal check");
            if ($result1 !== 0) {
                $this->assertTrue(
                    ($result1 > 0 && $result2 < 0) || ($result1 < 0 && $result2 > 0),
                    "Comparator {$name} not consistent: {$result1} and {$result2}"
                );
            }
        }
    }

    // ========== Edge Cases ==========

    public function testEmptyStrings(): void
    {
        $ciAsc = Comparators::ciAsc();
        $natural = Comparators::natural();
        $byLength = Comparators::byLength();
        
        $this->assertSame(0, $ciAsc('', ''));
        $this->assertSame(0, $natural('', ''));
        $this->assertSame(0, $byLength('', ''));
        
        $this->assertLessThan(0, $ciAsc('', 'a'));
        $this->assertLessThan(0, $natural('', 'a'));
        $this->assertLessThan(0, $byLength('', 'a'));
    }

    public function testSpecialCharacters(): void
    {
        $ciAsc = Comparators::ciAsc();
        
        // Note: strcasecmp doesn't handle Unicode properly, so café != CAFÉ
        // This is expected behavior for the basic strcasecmp function
        $this->assertNotSame(0, $ciAsc('café', 'CAFÉ'));
        $this->assertLessThan(0, $ciAsc('cafe', 'zebra'));
        
        // Test with numbers and special characters
        $natural = Comparators::natural();
        $this->assertLessThan(0, $natural('item1', 'item2'));
        $this->assertLessThan(0, $natural('item-1', 'item-10'));
    }

    public function testIntegerEdgeCases(): void
    {
        $intAsc = Comparators::intAsc();
        $intDesc = Comparators::intDesc();
        
        // Test with negative numbers
        $this->assertLessThan(0, $intAsc(-5, -3));
        $this->assertGreaterThan(0, $intDesc(-5, -3));
        
        // Test with zero
        $this->assertLessThan(0, $intAsc(-1, 0));
        $this->assertLessThan(0, $intAsc(0, 1));
        $this->assertSame(0, $intAsc(0, 0));
        
        // Test with large numbers
        $this->assertLessThan(0, $intAsc(999999, 1000000));
        $this->assertGreaterThan(0, $intDesc(999999, 1000000));
    }
}