<?php

declare(strict_types=1);

namespace CheckThisCloud\CrockfordRandom;

use Random\Randomizer;
use ValueError;

final class CrockfordRandom
{
    private const string ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    /**
     * @param list<string> $exclude Codes that must not be returned (case-insensitive).
     *                              Retries up to $maxAttempts before throwing.
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

    /**
     * @param list<string> $exclude
     */
    public static function generateLowercase(int $length, array $exclude = [], int $maxAttempts = 100): string
    {
        return strtolower(self::generate($length, $exclude, $maxAttempts));
    }
}