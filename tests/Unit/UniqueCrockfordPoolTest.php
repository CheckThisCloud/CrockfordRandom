<?php

declare(strict_types=1);

namespace CheckThisCloud\CrockfordRandom\Tests\Unit;

use CheckThisCloud\CrockfordRandom\Exception\InvalidLength;
use CheckThisCloud\CrockfordRandom\Exception\PoolExhausted;
use CheckThisCloud\CrockfordRandom\UniqueCrockfordPool;
use PHPUnit\Framework\TestCase;

class UniqueCrockfordPoolTest extends TestCase
{
    private const string ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    public function testConstructorWithPositiveLength(): void
    {
        $pool = new UniqueCrockfordPool(5);
        self::assertSame(0, $pool->issuedCount());
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

        self::assertSame(5, strlen($code));
        $this->assertMatchesPattern($code);
        self::assertSame(1, $pool->issuedCount());
    }

    public function testMultipleNextCallsGenerateUniqueCodes(): void
    {
        $pool = new UniqueCrockfordPool(4);
        $codes = [];

        for ($i = 0; $i < 100; $i++) {
            $codes[] = $pool->next();
        }

        $uniqueCodes = array_unique($codes);
        self::assertCount(100, $uniqueCodes, 'All generated codes should be unique');
        self::assertSame(100, $pool->issuedCount());
    }

    public function testHasIssuedReturnsTrueForIssuedCode(): void
    {
        $pool = new UniqueCrockfordPool(5);
        $code = $pool->next();

        self::assertTrue($pool->hasIssued($code));
        self::assertTrue($pool->hasIssued(strtolower($code)), 'Should be case-insensitive');
    }

    public function testHasIssuedReturnsFalseForNonIssuedCode(): void
    {
        $pool = new UniqueCrockfordPool(5);
        $pool->next();

        self::assertFalse($pool->hasIssued('00000'));
    }

    public function testTryNextReturnsNullOnExhaustion(): void
    {
        // Use length 1 for easy exhaustion (32 possibilities)
        $pool = new UniqueCrockfordPool(1);

        // Issue all 32 codes
        for ($i = 0; $i < 32; $i++) {
            $code = $pool->tryNext();
            self::assertNotNull($code);
        }

        // Pool should be exhausted
        self::assertNull($pool->tryNext());
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
        self::assertSame(32768, $pool->capacityInt()); // 32^3

        $pool2 = new UniqueCrockfordPool(5);
        self::assertSame(33554432, $pool2->capacityInt()); // 32^5
    }

    public function testCapacityString(): void
    {
        $pool = new UniqueCrockfordPool(3);
        self::assertSame('32768', $pool->capacityString());

        $pool2 = new UniqueCrockfordPool(12);
        self::assertSame('1152921504606846976', $pool2->capacityString());
    }

    public function testRemaining(): void
    {
        $pool = new UniqueCrockfordPool(3);
        $initialRemaining = $pool->remaining();
        self::assertSame(32768, $initialRemaining); // 32^3

        $pool->next();
        self::assertSame(32767, $pool->remaining());

        $pool->next();
        self::assertSame(32766, $pool->remaining());
    }

    public function testReset(): void
    {
        $pool = new UniqueCrockfordPool(3);
        $code1 = $pool->next();
        self::assertSame(1, $pool->issuedCount());
        self::assertTrue($pool->hasIssued($code1));

        $pool->reset();
        self::assertSame(0, $pool->issuedCount());
        self::assertFalse($pool->hasIssued($code1), 'Code should not be tracked after reset');
    }

    public function testReserve(): void
    {
        $pool = new UniqueCrockfordPool(4);
        $codes = $pool->reserve(10);

        self::assertCount(10, $codes);
        self::assertSame(10, $pool->issuedCount());

        // All codes should be unique
        $uniqueCodes = array_unique($codes);
        self::assertCount(10, $uniqueCodes);

        // All codes should be tracked as issued
        foreach ($codes as $code) {
            self::assertTrue($pool->hasIssued($code));
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

        self::assertSame(5, strlen($code));
        self::assertSame(strtolower($code), $code, 'Code should be lowercase');
        $this->assertMatchesPattern(strtoupper($code));
    }

    public function testConstructorThrowsWhenBrickMathNotInstalledForLargeLength(): void
    {
        // Skip test if brick/math is installed
        if (class_exists('Brick\Math\BigInteger')) {
            self::markTestSkipped('brick/math is installed');
        }

        $this->expectException(InvalidLength::class);
        $this->expectExceptionMessage('Length 13 requires brick/math library');

        new UniqueCrockfordPool(13);
    }

    public function testConstructorWorksWithBrickMathForLargeLength(): void
    {
        // Skip test if brick/math is not installed
        if (! class_exists('Brick\Math\BigInteger')) {
            self::markTestSkipped('brick/math is not installed');
        }

        $pool = new UniqueCrockfordPool(13);
        self::assertSame(0, $pool->issuedCount());
    }

    public function testCapacityStringWithBrickMathForLargeLength(): void
    {
        // Skip test if brick/math is not installed
        if (! class_exists('Brick\Math\BigInteger')) {
            self::markTestSkipped('brick/math is not installed');
        }

        $pool = new UniqueCrockfordPool(13);
        $capacity = $pool->capacityString();

        // 32^13 = 36893488147419103232
        self::assertSame('36893488147419103232', $capacity);
    }

    public function testNextWithBrickMathForLargeLength(): void
    {
        // Skip test if brick/math is not installed
        if (! class_exists('Brick\Math\BigInteger')) {
            self::markTestSkipped('brick/math is not installed');
        }

        $pool = new UniqueCrockfordPool(13);
        $code = $pool->next();

        self::assertSame(13, strlen($code));
        $this->assertMatchesPattern($code);
        self::assertSame(1, $pool->issuedCount());
    }

    public function testReserveForLargeLength(): void
    {
        // Skip test if brick/math is not installed
        if (! class_exists('Brick\Math\BigInteger')) {
            self::markTestSkipped('brick/math is not installed');
        }

        $pool = new UniqueCrockfordPool(13);
        $codes = $pool->reserve(5);

        self::assertCount(5, $codes);
        self::assertSame(5, $pool->issuedCount());
        
        foreach ($codes as $code) {
            self::assertSame(13, strlen($code));
            self::assertTrue($pool->hasIssued($code));
        }
    }

    public function testCapacityStringForVeryLargeLength(): void
    {
        // Skip test if brick/math is not installed
        if (! class_exists('Brick\Math\BigInteger')) {
            self::markTestSkipped('brick/math is not installed');
        }

        $pool = new UniqueCrockfordPool(20);
        // 32^20 = 1267650600228229401496703205376
        self::assertSame('1267650600228229401496703205376', $pool->capacityString());
    }

    public function testCapacityIntThrowsForLargeLength(): void
    {
        // Skip test if brick/math is not installed
        if (! class_exists('Brick\Math\BigInteger')) {
            self::markTestSkipped('brick/math is not installed');
        }

        $pool = new UniqueCrockfordPool(13);
        
        $this->expectException(InvalidLength::class);
        $this->expectExceptionMessage('Capacity exceeds PHP integer limits. Use capacityString().');
        
        $pool->capacityInt();
    }

    public function testRemainingBigIntForLargeLength(): void
    {
        // Skip test if brick/math is not installed
        if (! class_exists('Brick\Math\BigInteger')) {
            self::markTestSkipped('brick/math is not installed');
        }

        $pool = new UniqueCrockfordPool(13);
        
        // remainingBigInt should return correct large number
        // 32^13 = 36893488147419103232
        $remaining = $pool->remainingBigInt();
        self::assertSame('36893488147419103232', (string) $remaining);

        $pool->next();
        
        // remaining = capacity - 1
        $remaining = $pool->remainingBigInt();
        self::assertSame('36893488147419103231', (string) $remaining);
    }

    public function testNextLowercaseForLargeLength(): void
    {
        // Skip test if brick/math is not installed
        if (! class_exists('Brick\Math\BigInteger')) {
            self::markTestSkipped('brick/math is not installed');
        }

        $pool = new UniqueCrockfordPool(13);
        $code = $pool->nextLowercase();

        self::assertSame(13, strlen($code));
        self::assertSame(strtolower($code), $code);
        self::assertSame(1, $pool->issuedCount());
    }

    public function testConstructorAcceptsExcludedCodes(): void
    {
        $pool = new UniqueCrockfordPool(5, ['ABCDE', 'fghjk']);

        self::assertSame(0, $pool->issuedCount());
        self::assertSame(2, $pool->excludedCount());
        self::assertTrue($pool->isExcluded('ABCDE'));
        self::assertTrue($pool->isExcluded('abcde'), 'Should be case-insensitive');
        self::assertTrue($pool->isExcluded('FGHJK'), 'Lowercase input should be normalized');
        self::assertFalse($pool->hasIssued('ABCDE'), 'Excluded codes are not "issued"');
    }

    public function testConstructorThrowsOnMismatchedExcludedCodeLength(): void
    {
        $this->expectException(InvalidLength::class);
        $this->expectExceptionMessage('Excluded code ABC does not match pool length 5.');

        new UniqueCrockfordPool(5, ['ABC']);
    }

    public function testExcludeMethodAddsCodes(): void
    {
        $pool = new UniqueCrockfordPool(5);
        $pool->exclude(['ABCDE']);
        $pool->exclude(['fghjk', 'MNPQR']);

        self::assertSame(3, $pool->excludedCount());
        self::assertTrue($pool->isExcluded('ABCDE'));
        self::assertTrue($pool->isExcluded('FGHJK'));
        self::assertTrue($pool->isExcluded('MNPQR'));
    }

    public function testExcludedCodesAreNeverIssued(): void
    {
        // Length 1, exclude 31 of 32 possible codes — only '0' remains.
        $allButZero = [];
        foreach (str_split('123456789ABCDEFGHJKMNPQRSTVWXYZ') as $char) {
            $allButZero[] = $char;
        }

        $pool = new UniqueCrockfordPool(1, $allButZero);

        self::assertSame(31, $pool->excludedCount());
        self::assertSame(1, $pool->remaining());

        $code = $pool->next();
        self::assertSame('0', $code);

        // Pool should now be exhausted.
        $this->expectException(PoolExhausted::class);
        $pool->next();
    }

    public function testExcludedCountReducesRemaining(): void
    {
        $pool = new UniqueCrockfordPool(1, ['A', 'B', 'C']);
        self::assertSame(29, $pool->remaining()); // 32 - 3
        $pool->next();
        self::assertSame(28, $pool->remaining());
    }

    public function testReserveRespectsExcludedCapacity(): void
    {
        $pool = new UniqueCrockfordPool(1); // capacity 32
        $pool->exclude(str_split('0123456789ABCDEFGHJKMNPQRSTVWXYZ')); // exclude all 32

        $this->expectException(PoolExhausted::class);
        $this->expectExceptionMessage('Reserving 1 codes would exceed pool capacity of 32.');
        $pool->reserve(1);
    }

    public function testResetDoesNotForgetExcluded(): void
    {
        $pool = new UniqueCrockfordPool(5, ['ABCDE']);
        $pool->next();

        $pool->reset();

        self::assertSame(0, $pool->issuedCount());
        self::assertSame(1, $pool->excludedCount(), 'Excluded codes survive reset');
        self::assertTrue($pool->isExcluded('ABCDE'));
    }

    public function testExcludeIsIdempotent(): void
    {
        $pool = new UniqueCrockfordPool(1);
        $pool->exclude(str_split('0123456789ABCDEFGHJKMNPQRSTVWXYZ'));
        self::assertSame(32, $pool->excludedCount());

        // Re-excluding the same codes is a no-op.
        $pool->exclude(['0', 'A']);
        self::assertSame(32, $pool->excludedCount());
    }

    private function assertMatchesPattern(string $code): void
    {
        for ($i = 0, $iMax = strlen($code); $i < $iMax; $i++) {
            $char = $code[$i];
            self::assertStringContainsString(
                $char,
                self::ALPHABET,
                "Character '{$char}' at position {$i} should be in alphabet"
            );
        }
    }
}
