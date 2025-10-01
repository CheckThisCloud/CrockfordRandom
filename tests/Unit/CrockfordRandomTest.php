<?php
declare(strict_types=1);

namespace CheckThisCloud\CrockfordRandom\Tests\Unit;

use CheckThisCloud\CrockfordRandom\CrockfordRandom;
use PHPUnit\Framework\TestCase;
use ValueError;

class CrockfordRandomTest extends TestCase
{
    private const ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    public function testGeneratePositiveLength(): void
    {
        for ($length = 1; $length <= 20; $length++) {
            $result = CrockfordRandom::generate($length);
            $this->assertSame($length, strlen($result));
        }
    }

    public function testGenerateNegativeLengthThrowsException(): void
    {
        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('Length must be non-negative');
        
        CrockfordRandom::generate(-1);
    }

    public function testGenerateNegativeLengthThrowsExceptionForLargeNegative(): void
    {
        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('Length must be non-negative');
        
        CrockfordRandom::generate(-100);
    }

    public function testGeneratedStringContainsOnlyValidCharacters(): void
    {
        $lengths = [1, 5, 10, 32, 100];
        
        foreach ($lengths as $length) {
            $result = CrockfordRandom::generate($length);
            
            for ($i = 0; $i < strlen($result); $i++) {
                $char = $result[$i];
                $this->assertStringContainsString(
                    $char,
                    self::ALPHABET,
                    "Character '{$char}' should be in alphabet"
                );
            }
        }
    }

    public function testGenerateReturnsDifferentResultsOnMultipleCalls(): void
    {
        $length = 20;
        $results = [];
        
        // Generate multiple results and check they're different
        for ($i = 0; $i < 10; $i++) {
            $result = CrockfordRandom::generate($length);
            $results[] = $result;
        }
        
        // Check that we have at least some different results
        $uniqueResults = array_unique($results);
        $this->assertGreaterThan(
            1,
            count($uniqueResults),
            'Multiple calls should produce different results (got ' . count($uniqueResults) . ' unique out of 10)'
        );
    }

    public function testGenerateLargeLength(): void
    {
        $length = 1000;
        $result = CrockfordRandom::generate($length);
        
        $this->assertSame($length, strlen($result));
        
        // Verify all characters are valid
        for ($i = 0; $i < strlen($result); $i++) {
            $char = $result[$i];
            $this->assertStringContainsString(
                $char,
                self::ALPHABET,
                "Character '{$char}' at position {$i} should be in alphabet"
            );
        }
    }


    public function testGenerateLowercasePositiveLength(): void
    {
        for ($length = 1; $length <= 20; $length++) {
            $result = CrockfordRandom::generateLowercase($length);
            $this->assertSame($length, strlen($result));
        }
    }

    public function testGenerateLowercaseNegativeLengthThrowsException(): void
    {
        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('Length must be non-negative');
        CrockfordRandom::generateLowercase(-1);
    }

    public function testGenerateLowercaseContainsOnlyValidLowercaseCharacters(): void
    {
        $alphabetLower = strtolower(self::ALPHABET);
        $lengths = [1, 5, 10, 32, 100];
        foreach ($lengths as $length) {
            $result = CrockfordRandom::generateLowercase($length);
            for ($i = 0; $i < strlen($result); $i++) {
                $char = $result[$i];
                $this->assertStringContainsString(
                    $char,
                    $alphabetLower,
                    "Character '{$char}' should be in lowercase alphabet"
                );
            }
        }
    }

    public function testGenerateLowercaseReturnsDifferentResultsOnMultipleCalls(): void
    {
        $length = 20;
        $results = [];
        for ($i = 0; $i < 10; $i++) {
            $result = CrockfordRandom::generateLowercase($length);
            $results[] = $result;
        }
        $uniqueResults = array_unique($results);
        $this->assertGreaterThan(
            1,
            count($uniqueResults),
            'Multiple calls should produce different results (got ' . count($uniqueResults) . ' unique out of 10)'
        );
    }
}