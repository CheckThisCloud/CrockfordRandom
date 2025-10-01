<?php

declare(strict_types=1);

namespace CheckThisCloud\CrockfordRandom;

use Random\Randomizer;
use ValueError;

final class CrockfordRandom
{
    private const string ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    public static function generate(int $length): string
    {
        if ($length < 0) {
            throw new ValueError('Length must be non-negative');
        }
        if ($length === 0) {
            return '';
        }

        $randomizer = new Randomizer();
        return $randomizer->getBytesFromString(self::ALPHABET, $length);
    }

    public static function generateLowercase(int $length): string
    {
        return strtolower(self::generate($length));
    }
}