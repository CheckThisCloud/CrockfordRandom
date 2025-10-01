<?php

declare(strict_types=1);

namespace CheckThisCloud\CrockfordRandom\Tests\Unit;

use CheckThisCloud\CrockfordRandom\Exception\InvalidLength;
use CheckThisCloud\CrockfordRandom\Exception\PoolExhausted;
use CheckThisCloud\CrockfordRandom\UniqueCrockfordPool;
use PHPUnit\Framework\TestCase;

class UniqueCrockfordPoolTest extends TestCase
{
    private const ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    public function testConstructorWithPositiveLength(): void
    {
        $pool = new UniqueCrockfordPool(5);
        $this->assertSame(0, $pool->issuedCount());
    }

    public function testConstructorThrowsOnZeroLength(): void
    {
        $this->expectException(InvalidLength::class);
        $this->expectExceptionMessage('Length must be positive.');
        
        new UniqueCrockfordPool(0);
    }

    public function testConstructorThrowsOnNegativeLength(): void
    {
        $this->expectException(InvalidLength::class);
        $this->expectExceptionMessage('Length must be positive.');
        
        new UniqueCrockfordPool(-5);
    }

    public function testNextGeneratesUniqueCode(): void
    {
        $pool = new UniqueCrockfordPool(5);
        $code = $pool->next();
        
        $this->assertSame(5, strlen($code));
        $this->assertMatchesPattern($code);
        $this->assertSame(1, $pool->issuedCount());
    }

    public function testMultipleNextCallsGenerateUniqueCodes(): void
    {
        $pool = new UniqueCrockfordPool(4);
        $codes = [];
        
        for ($i = 0; $i < 100; $i++) {
            $codes[] = $pool->next();
        }
        
        $uniqueCodes = array_unique($codes);
        $this->assertCount(100, $uniqueCodes, 'All generated codes should be unique');
        $this->assertSame(100, $pool->issuedCount());
    }

    public function testHasIssuedReturnsTrueForIssuedCode(): void
    {
        $pool = new UniqueCrockfordPool(5);
        $code = $pool->next();
        
        $this->assertTrue($pool->hasIssued($code));
        $this->assertTrue($pool->hasIssued(strtolower($code)), 'Should be case-insensitive');
    }

    public function testHasIssuedReturnsFalseForNonIssuedCode(): void
    {
        $pool = new UniqueCrockfordPool(5);
        $pool->next();
        
        $this->assertFalse($pool->hasIssued('00000'));
    }

    public function testTryNextReturnsNullOnExhaustion(): void
    {
        // Use length 1 for easy exhaustion (32 possibilities)
        $pool = new UniqueCrockfordPool(1);
        
        // Issue all 32 codes
        for ($i = 0; $i < 32; $i++) {
            $code = $pool->tryNext();
            $this->assertNotNull($code);
        }
        
        // Pool should be exhausted
        $this->assertNull($pool->tryNext());
    }

    public function testNextThrowsOnExhaustion(): void
    {
        // Use length 1 for easy exhaustion (32 possibilities)
        $pool = new UniqueCrockfordPool(1);
        
        // Issue all 32 codes
        for ($i = 0; $i < 32; $i++) {
            $pool->next();
        }
        
        // Pool should be exhausted
        $this->expectException(PoolExhausted::class);
        $this->expectExceptionMessage('All unique codes of length 1 have been issued.');
        $pool->next();
    }

    public function testCapacityInt(): void
    {
        $pool = new UniqueCrockfordPool(3);
        $this->assertSame(32768, $pool->capacityInt()); // 32^3
        
        $pool2 = new UniqueCrockfordPool(5);
        $this->assertSame(33554432, $pool2->capacityInt()); // 32^5
    }

    public function testCapacityString(): void
    {
        $pool = new UniqueCrockfordPool(3);
        $this->assertSame('32768', $pool->capacityString());
        
        $pool2 = new UniqueCrockfordPool(12);
        $this->assertSame('1152921504606846976', $pool2->capacityString());
    }

    public function testRemaining(): void
    {
        $pool = new UniqueCrockfordPool(3);
        $initialRemaining = $pool->remaining();
        $this->assertSame(32768, $initialRemaining); // 32^3
        
        $pool->next();
        $this->assertSame(32767, $pool->remaining());
        
        $pool->next();
        $this->assertSame(32766, $pool->remaining());
    }

    public function testReset(): void
    {
        $pool = new UniqueCrockfordPool(3);
        $code1 = $pool->next();
        $this->assertSame(1, $pool->issuedCount());
        $this->assertTrue($pool->hasIssued($code1));
        
        $pool->reset();
        $this->assertSame(0, $pool->issuedCount());
        $this->assertFalse($pool->hasIssued($code1), 'Code should not be tracked after reset');
    }

    public function testReserve(): void
    {
        $pool = new UniqueCrockfordPool(4);
        $codes = $pool->reserve(10);
        
        $this->assertCount(10, $codes);
        $this->assertSame(10, $pool->issuedCount());
        
        // All codes should be unique
        $uniqueCodes = array_unique($codes);
        $this->assertCount(10, $uniqueCodes);
        
        // All codes should be tracked as issued
        foreach ($codes as $code) {
            $this->assertTrue($pool->hasIssued($code));
        }
    }

    public function testReserveThrowsOnNegativeCount(): void
    {
        $pool = new UniqueCrockfordPool(4);
        
        $this->expectException(InvalidLength::class);
        $this->expectExceptionMessage('Count must be positive.');
        $pool->reserve(-1);
    }

    public function testReserveThrowsOnZeroCount(): void
    {
        $pool = new UniqueCrockfordPool(4);
        
        $this->expectException(InvalidLength::class);
        $this->expectExceptionMessage('Count must be positive.');
        $pool->reserve(0);
    }

    public function testReserveThrowsOnExceedingCapacity(): void
    {
        $pool = new UniqueCrockfordPool(1); // capacity = 32
        
        $this->expectException(PoolExhausted::class);
        $this->expectExceptionMessage('Reserving 50 codes would exceed pool capacity of 32.');
        $pool->reserve(50);
    }

    public function testNextLowercase(): void
    {
        $pool = new UniqueCrockfordPool(5);
        $code = $pool->nextLowercase();
        
        $this->assertSame(5, strlen($code));
        $this->assertSame(strtolower($code), $code, 'Code should be lowercase');
        $this->assertMatchesPattern(strtoupper($code));
    }

    public function testConstructorThrowsWhenBrickMathNotInstalledForLargeLength(): void
    {
        // Skip test if brick/math is installed
        if (class_exists('Brick\Math\BigInteger')) {
            $this->markTestSkipped('brick/math is installed');
        }

        $this->expectException(InvalidLength::class);
        $this->expectExceptionMessage('Length 13 requires brick/math library');
        
        new UniqueCrockfordPool(13);
    }

    public function testConstructorWorksWithBrickMathForLargeLength(): void
    {
        // Skip test if brick/math is not installed
        if (!class_exists('Brick\Math\BigInteger')) {
            $this->markTestSkipped('brick/math is not installed');
        }

        $pool = new UniqueCrockfordPool(13);
        $this->assertSame(0, $pool->issuedCount());
    }

    public function testCapacityStringWithBrickMathForLargeLength(): void
    {
        // Skip test if brick/math is not installed
        if (!class_exists('Brick\Math\BigInteger')) {
            $this->markTestSkipped('brick/math is not installed');
        }

        $pool = new UniqueCrockfordPool(13);
        $capacity = $pool->capacityString();
        
        // 32^13 = 36893488147419103232
        $this->assertSame('36893488147419103232', $capacity);
    }

    public function testNextWithBrickMathForLargeLength(): void
    {
        // Skip test if brick/math is not installed
        if (!class_exists('Brick\Math\BigInteger')) {
            $this->markTestSkipped('brick/math is not installed');
        }

        $pool = new UniqueCrockfordPool(13);
        $code = $pool->next();
        
        $this->assertSame(13, strlen($code));
        $this->assertMatchesPattern($code);
        $this->assertSame(1, $pool->issuedCount());
    }

    private function assertMatchesPattern(string $code): void
    {
        for ($i = 0; $i < strlen($code); $i++) {
            $char = $code[$i];
            $this->assertStringContainsString(
                $char,
                self::ALPHABET,
                "Character '{$char}' at position {$i} should be in alphabet"
            );
        }
    }
}
