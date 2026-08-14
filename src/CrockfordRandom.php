<?php

declare(strict_types=1);

namespace CheckThisCloud\CrockfordRandom;

use Random\Randomizer;
use ValueError;

final class CrockfordRandom
{
    public const string ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    /**
     * @param list<string> $exclude Codes that must not be returned (case-insensitive).
     * @param int $maxAttempts Retry budget for lengths above 12. Ignored below that, where
     *                         selection is exact and cannot fail while a code remains.
     */
    public static function generate(int $length, array $exclude = [], int $maxAttempts = 100): string
    {
        if ($length <= 0) {
            throw new ValueError('Length must be positive');
        }

        $randomizer = new Randomizer();

        if ($exclude === []) {
            return $randomizer->getBytesFromString(self::ALPHABET, $length);
        }

        // Above length 12 the keyspace (32^13 and up) no longer fits a native int, but it
        // also dwarfs any exclusion list that fits in memory, so retrying is safe there.
        if ($length > 12) {
            $excludeSet = [];
            foreach ($exclude as $code) {
                $excludeSet[strtoupper($code)] = true;
            }

            for ($i = 0; $i < $maxAttempts; $i++) {
                $code = $randomizer->getBytesFromString(self::ALPHABET, $length);
                if (!isset($excludeSet[$code])) {
                    return $code;
                }
            }

            throw new \RuntimeException(
                sprintf('Could not generate a non-excluded code of length %d after %d attempts.', $length, $maxAttempts)
            );
        }

        // Codes the generator could never return must not count against the keyspace,
        // or they would shrink it for nothing. Iterating $exclude rather than a set
        // keyed by code: PHP would coerce a numeric code like '007' to the int 7.
        $taken = [];
        foreach ($exclude as $code) {
            $code = strtoupper($code);
            if (strlen($code) === $length && strspn($code, self::ALPHABET) === $length) {
                $taken[self::toIndex($code)] = true;
            }
        }

        $available = (32 ** $length) - count($taken);

        if ($available <= 0) {
            throw new \RuntimeException(sprintf('Every code of length %d is excluded.', $length));
        }

        // Take the n-th code that is not excluded, by stepping the drawn index over
        // every excluded index at or below it. Uniform, and never runs out of tries.
        $index = $randomizer->getInt(0, $available - 1);
        $sorted = array_keys($taken);
        sort($sorted);
        foreach ($sorted as $excluded) {
            if ($excluded > $index) {
                break;
            }
            $index++;
        }

        return self::fromIndex($index, $length);
    }

    /**
     * @param list<string> $exclude
     */
    public static function generateLowercase(int $length, array $exclude = [], int $maxAttempts = 100): string
    {
        return strtolower(self::generate($length, $exclude, $maxAttempts));
    }

    private static function toIndex(string $code): int
    {
        $index = 0;
        for ($i = 0, $max = strlen($code); $i < $max; $i++) {
            $index = ($index * 32) + (int) strpos(self::ALPHABET, $code[$i]);
        }

        return $index;
    }

    private static function fromIndex(int $index, int $length): string
    {
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code = self::ALPHABET[$index % 32] . $code;
            $index = intdiv($index, 32);
        }

        return $code;
    }
}
