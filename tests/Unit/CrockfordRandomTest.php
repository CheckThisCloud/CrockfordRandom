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
            self::assertSame($length, strlen($result));
        }
    }

    public function testGenerateNegativeLengthThrowsException(): void
    {
        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('Length must be positive');
        
        CrockfordRandom::generate(-1);
    }

    public function testGenerateNegativeLengthThrowsExceptionForLargeNegative(): void
    {
        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('Length must be positive');
        
        CrockfordRandom::generate(-100);
    }

    public function testGeneratedStringContainsOnlyValidCharacters(): void
    {
        $lengths = [1, 5, 10, 32, 100];
        
        foreach ($lengths as $length) {
            $result = CrockfordRandom::generate($length);
            
            for ($i = 0; $i < strlen($result); $i++) {
                $char = $result[$i];
                self::assertStringContainsString(
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
        self::assertGreaterThan(
            1,
            count($uniqueResults),
            'Multiple calls should produce different results (got ' . count($uniqueResults) . ' unique out of 10)'
        );
    }

    public function testGenerateLargeLength(): void
    {
        $length = 1000;
        $result = CrockfordRandom::generate($length);
        
        self::assertSame($length, strlen($result));
        
        // Verify all characters are valid
        for ($i = 0; $i < strlen($result); $i++) {
            $char = $result[$i];
            self::assertStringContainsString(
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
            self::assertSame($length, strlen($result));
        }
    }

    public function testGenerateLowercaseNegativeLengthThrowsException(): void
    {
        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('Length must be positive');
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
                self::assertStringContainsString(
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
        self::assertGreaterThan(
            1,
            count($uniqueResults),
            'Multiple calls should produce different results (got ' . count($uniqueResults) . ' unique out of 10)'
        );
    }

    public function testGenerateRespectsExclusions(): void
    {
        // Length 1 with 31 of 32 codes excluded forces the only remaining code.
        $exclude = str_split('123456789ABCDEFGHJKMNPQRSTVWXYZ');
        $result = CrockfordRandom::generate(1, $exclude);
        self::assertSame('0', $result);
    }

    public function testGenerateExclusionIsCaseInsensitive(): void
    {
        $exclude = str_split('123456789abcdefghjkmnpqrstvwxyz');
        $result = CrockfordRandom::generate(1, $exclude);
        self::assertSame('0', $result);
    }

    public function testGenerateDoesNotReturnExcludedCode(): void
    {
        // Less contrived: at length 2 (1024 codes), exclude 100 and verify output isn't among them.
        $exclude = [];
        for ($i = 0; $i < 100; $i++) {
            $exclude[] = CrockfordRandom::generate(2);
        }
        $exclude = array_values(array_unique($exclude));

        for ($i = 0; $i < 50; $i++) {
            $result = CrockfordRandom::generate(2, $exclude);
            self::assertNotContains($result, $exclude);
        }
    }

    public function testGenerateLowercaseRespectsExclusions(): void
    {
        $exclude = str_split('123456789ABCDEFGHJKMNPQRSTVWXYZ');
        $result = CrockfordRandom::generateLowercase(1, $exclude);
        self::assertSame('0', $result);
    }
    public function testGenerateNeverFailsWhileANonExcludedCodeExists(): void
    {
        // 31 of 32 codes excluded: '0' is the only possible answer, so every call
        // must return it. Rejection sampling gives up before finding it.
        $exclude = str_split('123456789ABCDEFGHJKMNPQRSTVWXYZ');

        for ($i = 0; $i < 200; $i++) {
            self::assertSame('0', CrockfordRandom::generate(1, $exclude));
        }
    }

    public function testGenerateThrowsOnlyWhenEveryCodeIsExcluded(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Every code of length 1 is excluded.');

        CrockfordRandom::generate(1, str_split('0123456789ABCDEFGHJKMNPQRSTVWXYZ'));
    }

    public function testGenerateSelectsAmongAllRemainingCodes(): void
    {
        // 30 of 32 excluded leaves exactly '0' and '1'; both must be reachable.
        $exclude = str_split('23456789ABCDEFGHJKMNPQRSTVWXYZ');

        // Collected as values, not keys: PHP would coerce '0'/'1' keys to ints.
        $seen = [];
        for ($i = 0; $i < 300; $i++) {
            $seen[] = CrockfordRandom::generate(1, $exclude);
        }

        $seen = array_values(array_unique($seen));
        sort($seen);
        self::assertSame(['0', '1'], $seen);
    }

    public function testGenerateIgnoresExclusionsItCouldNeverProduce(): void
    {
        // I, L, O and U are not in the alphabet and 'AB' is the wrong length, so
        // none of them may be counted against the keyspace.
        $exclude = array_merge(
            str_split('123456789ABCDEFGHJKMNPQRSTVWXYZ'),
            ['I', 'L', 'O', 'U', 'AB']
        );

        self::assertSame('0', CrockfordRandom::generate(1, $exclude));
    }

    public function testGenerateWithExclusionsReturnsWellFormedCodes(): void
    {
        // Exercises the multi-character index round-trip.
        $exclude = [];
        for ($i = 0; $i < 500; $i++) {
            $exclude[] = CrockfordRandom::generate(3);
        }
        $exclude = array_values(array_unique($exclude));

        for ($i = 0; $i < 200; $i++) {
            $code = CrockfordRandom::generate(3, $exclude);

            self::assertSame(3, strlen($code));
            self::assertNotContains($code, $exclude);
            $this->assertOnlyAlphabetCharacters($code);
        }
    }

    public function testGenerateStillAcceptsTheMaxAttemptsArgument(): void
    {
        // Released in v1.1.0, so both call forms must keep working. maxAttempts is
        // deliberately far too low for rejection sampling: exact selection ignores it.
        $exclude = str_split('123456789ABCDEFGHJKMNPQRSTVWXYZ');

        self::assertSame('0', CrockfordRandom::generate(1, $exclude, 5));
        self::assertSame('0', CrockfordRandom::generate(1, $exclude, maxAttempts: 5));
        self::assertSame('0', CrockfordRandom::generateLowercase(1, $exclude, maxAttempts: 5));
    }

    public function testGenerateAboveNativeLengthStillExcludes(): void
    {
        // Above length 12 the keyspace no longer fits a native int, so this takes the
        // rejection-sampling path instead of exact selection.
        $exclude = [];
        for ($i = 0; $i < 50; $i++) {
            $exclude[] = CrockfordRandom::generate(13);
        }

        for ($i = 0; $i < 50; $i++) {
            $code = CrockfordRandom::generate(13, $exclude);

            self::assertSame(13, strlen($code));
            self::assertNotContains($code, $exclude);
            $this->assertOnlyAlphabetCharacters($code);
        }
    }

    public function testGenerateAboveNativeLengthHonoursMaxAttempts(): void
    {
        // The only deterministic way to reach the give-up branch: 32^13 is far too
        // large to exhaust, and Randomizer is not injectable, so a collision cannot
        // be forced. A zero budget permits no draw at all.
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Could not generate a non-excluded code of length 13 after 0 attempts.');

        CrockfordRandom::generate(13, ['0000000000000'], maxAttempts: 0);
    }

    private function assertOnlyAlphabetCharacters(string $code): void
    {
        self::assertSame(
            strlen($code),
            strspn($code, CrockfordRandom::ALPHABET),
            "Code '{$code}' contains characters outside the Crockford alphabet"
        );
    }
}
