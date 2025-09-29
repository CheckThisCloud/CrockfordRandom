<?php
declare(strict_types=1);

namespace Tests\Unit\Util;

require_once __DIR__ . '/../../../src/Util/CrockfordRandom.php';

use App\Util\CrockfordRandom;
use ValueError;
use Throwable;

class CrockfordRandomTest
{
    private const ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';
    
    private int $assertions = 0;
    private int $failures = 0;

    public function run(): void
    {
        echo "Running CrockfordRandom tests...\n\n";
        
        $this->testGenerateZeroLength();
        $this->testGeneratePositiveLength();
        $this->testGenerateNegativeLengthThrowsException();
        $this->testGeneratedStringContainsOnlyValidCharacters();
        $this->testGenerateReturnsDifferentResultsOnMultipleCalls();
        $this->testGenerateLargeLength();
        
        echo "\n";
        echo "Tests completed: {$this->assertions} assertions, {$this->failures} failures\n";
        
        if ($this->failures > 0) {
            exit(1);
        }
    }

    private function testGenerateZeroLength(): void
    {
        echo "Testing zero length generation...\n";
        $result = CrockfordRandom::generate(0);
        $this->assertEqual('', $result, 'Zero length should return empty string');
        echo "✓ Zero length test passed\n\n";
    }

    private function testGeneratePositiveLength(): void
    {
        echo "Testing positive length generation...\n";
        
        for ($length = 1; $length <= 20; $length++) {
            $result = CrockfordRandom::generate($length);
            $this->assertEqual($length, strlen($result), "Generated string should have length {$length}");
        }
        
        echo "✓ Positive length tests passed\n\n";
    }

    private function testGenerateNegativeLengthThrowsException(): void
    {
        echo "Testing negative length exception...\n";
        
        $this->assertThrows(function() {
            CrockfordRandom::generate(-1);
        }, ValueError::class, 'Length must be non-negative');
        
        $this->assertThrows(function() {
            CrockfordRandom::generate(-100);
        }, ValueError::class, 'Length must be non-negative');
        
        echo "✓ Negative length exception tests passed\n\n";
    }

    private function testGeneratedStringContainsOnlyValidCharacters(): void
    {
        echo "Testing character validity...\n";
        
        // Test with various lengths
        foreach ([1, 5, 10, 32, 100] as $length) {
            $result = CrockfordRandom::generate($length);
            
            for ($i = 0; $i < strlen($result); $i++) {
                $char = $result[$i];
                $this->assertTrue(
                    strpos(self::ALPHABET, $char) !== false,
                    "Character '{$char}' should be in alphabet"
                );
            }
        }
        
        echo "✓ Character validity tests passed\n\n";
    }

    private function testGenerateReturnsDifferentResultsOnMultipleCalls(): void
    {
        echo "Testing randomness...\n";
        
        $length = 20;
        $results = [];
        
        // Generate multiple results and check they're different
        for ($i = 0; $i < 10; $i++) {
            $result = CrockfordRandom::generate($length);
            $results[] = $result;
        }
        
        // Check that we have at least some different results
        $uniqueResults = array_unique($results);
        $this->assertTrue(
            count($uniqueResults) > 1,
            'Multiple calls should produce different results (got ' . count($uniqueResults) . ' unique out of 10)'
        );
        
        echo "✓ Randomness tests passed\n\n";
    }

    private function testGenerateLargeLength(): void
    {
        echo "Testing large length generation...\n";
        
        $length = 1000;
        $result = CrockfordRandom::generate($length);
        
        $this->assertEqual($length, strlen($result), "Large string should have correct length");
        
        // Verify all characters are valid
        for ($i = 0; $i < strlen($result); $i++) {
            $char = $result[$i];
            $this->assertTrue(
                strpos(self::ALPHABET, $char) !== false,
                "Character '{$char}' at position {$i} should be in alphabet"
            );
        }
        
        echo "✓ Large length tests passed\n\n";
    }

    private function assertEqual($expected, $actual, string $message): void
    {
        $this->assertions++;
        if ($expected !== $actual) {
            $this->failures++;
            echo "✗ FAIL: {$message}\n";
            echo "  Expected: " . var_export($expected, true) . "\n";
            echo "  Actual: " . var_export($actual, true) . "\n";
        }
    }

    private function assertTrue(bool $condition, string $message): void
    {
        $this->assertions++;
        if (!$condition) {
            $this->failures++;
            echo "✗ FAIL: {$message}\n";
        }
    }

    private function assertThrows(callable $callback, string $expectedException, string $expectedMessage): void
    {
        $this->assertions++;
        try {
            $callback();
            $this->failures++;
            echo "✗ FAIL: Expected {$expectedException} to be thrown\n";
        } catch (Throwable $e) {
            if (!($e instanceof $expectedException)) {
                $this->failures++;
                echo "✗ FAIL: Expected {$expectedException}, got " . get_class($e) . "\n";
            } elseif ($e->getMessage() !== $expectedMessage) {
                $this->failures++;
                echo "✗ FAIL: Expected message '{$expectedMessage}', got '{$e->getMessage()}'\n";
            }
        }
    }
}

// Run tests
$test = new CrockfordRandomTest();
$test->run();