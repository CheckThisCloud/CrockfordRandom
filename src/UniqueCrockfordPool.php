<?php

declare(strict_types=1);

namespace CheckThisCloud\CrockfordRandom;

use CheckThisCloud\CrockfordRandom\Exception\InvalidLength;
use CheckThisCloud\CrockfordRandom\Exception\PoolExhausted;

/**
 * @see CrockfordRandom::generate() for the encoding details.
 * @version Experimental: API and implementation may change without a major version bump.
 */
final class UniqueCrockfordPool
{
    /** @var array<string, true> Used as a set for O(1) lookups. */
    private array $storage;

    /**
     * Maximum length that can be handled with native PHP int (32^12 < PHP_INT_MAX).
     * For lengths > 12, brick/math is required.
     */
    private const int MAX_NATIVE_LENGTH = 12;

    /**
     * Create a pool that yields unique Crockford Base32 codes of fixed $length.
     */
    public function __construct(private readonly int $length)
    {
        if ($length <= 0) {
            throw new InvalidLength('Length must be positive.');
        }

        // Check if brick/math is needed but not available
        if ($length > self::MAX_NATIVE_LENGTH && !class_exists('Brick\Math\BigInteger')) {
            throw new InvalidLength(
                sprintf(
                    'Length %d requires brick/math library. Install it with: composer require brick/math. ' .
                    'For lengths up to %d, brick/math is not required.',
                    $length,
                    self::MAX_NATIVE_LENGTH
                )
            );
        }

        $this->storage = [];
    }

    /**
     * Get the next unique code (uppercase Crockford Base32).
     *
     * @throws PoolExhausted If all codes of this length are exhausted.
     */
    public function next(): string
    {
        // Check if pool is exhausted
        if ($this->isExhausted()) {
            throw new PoolExhausted(
                sprintf("All unique codes of length %d have been issued.", $this->length)
            );
        }

        do {
            $code = CrockfordRandom::generate($this->length);
        } while ($this->hasIssued($code));

        $this->storage[$code] = true;
        return $code;
    }

    /**
     * Try to get the next unique code without throwing.
     * Returns null on exhaustion.
     */
    public function tryNext(): ?string
    {
        try {
            return $this->next();
        } catch (PoolExhausted) {
            return null;
        }
    }

    /**
     * Number of unique codes produced so far in this pool.
     *
     * @return int
     */
    public function issuedCount(): int
    {
        return count($this->storage);
    }

    /**
     * The total capacity of this pool = 32^length.
     * (Crockford alphabet size is 32.)
     *
     * @return int May overflow PHP int for large lengths (length > 12); use capacityString() for exact values.
     */
    public function capacityInt(): int
    {
        return (int) pow(32, $this->length);
    }

    /**
     * Get capacity as a string for lengths that would overflow native int.
     * For length <= 12, returns the int value as string.
     * For length > 12, uses brick/math if available.
     *
     * @return string The capacity as a string representation
     */
    public function capacityString(): string
    {
        if ($this->length <= self::MAX_NATIVE_LENGTH) {
            return (string) $this->capacityInt();
        }

        // For large lengths, we need brick/math
        if (!class_exists('Brick\Math\BigInteger')) {
            throw new \RuntimeException(
                'brick/math is required for pool lengths > ' . self::MAX_NATIVE_LENGTH
            );
        }

        $bigInt = \Brick\Math\BigInteger::of(32)->power($this->length);
        return (string) $bigInt;
    }

    /**
     * Check if this pool is exhausted (all codes issued).
     * Works correctly for all pool sizes.
     *
     * @return bool
     */
    private function isExhausted(): bool
    {
        $issued = $this->issuedCount();
        
        if ($this->length <= self::MAX_NATIVE_LENGTH) {
            // Use native int comparison for small pools
            return $issued >= $this->capacityInt();
        }

        // For large pools, use brick/math
        if (!class_exists('Brick\Math\BigInteger')) {
            throw new \RuntimeException(
                'brick/math is required for pool lengths > ' . self::MAX_NATIVE_LENGTH
            );
        }

        $issuedBig = \Brick\Math\BigInteger::of($issued);
        $capacityBig = \Brick\Math\BigInteger::of(32)->power($this->length);
        return $issuedBig->isGreaterThanOrEqualTo($capacityBig);
    }

    /**
     * Best-effort remaining estimate: capacity() - issuedCount().
     * Same overflow caveat as capacity().
     */
    public function remaining(): int
    {
        return $this->capacityInt() - $this->issuedCount();
    }

    /**
     * Reset this pool (forgets all previously issued codes).
     * Useful in tests; be explicit this only affects this instance.
     */
    public function reset(): void
    {
        $this->storage = [];
    }

    /**
     * Returns true if the given code (case-insensitive) was already issued
     * by this pool. Normalizes to uppercase internally.
     */
    public function hasIssued(string $code): bool
    {
        $code = strtoupper($code);
        return isset($this->storage[$code]);
    }

    /**
     * Reserve a batch of unique codes upfront and return them.
     * Either returns exactly $count codes or throws on exhaustion.
     *
     * @return list<string>
     * @throws PoolExhausted
     */
    public function reserve(int $count): array
    {
        if ($count <= 0) {
            throw new InvalidLength('Count must be positive.');
        }

        // Check if reservation would exceed capacity
        $issued = $this->issuedCount();
        
        if ($this->length <= self::MAX_NATIVE_LENGTH) {
            // Use native int for small pools
            if ($issued + $count > $this->capacityInt()) {
                throw new PoolExhausted(
                    sprintf("Reserving %d codes would exceed pool capacity of %d.", $count, $this->capacityInt())
                );
            }
        } else {
            // Use brick/math for large pools
            if (!class_exists('Brick\Math\BigInteger')) {
                throw new \RuntimeException(
                    'brick/math is required for pool lengths > ' . self::MAX_NATIVE_LENGTH
                );
            }

            $issuedPlusCount = \Brick\Math\BigInteger::of((string) $issued)->plus(\Brick\Math\BigInteger::of((string) $count));
            $capacity = \Brick\Math\BigInteger::of(32)->power($this->length);
            
            if ($issuedPlusCount->isGreaterThan($capacity)) {
                throw new PoolExhausted(
                    sprintf("Reserving %d codes would exceed pool capacity of %s.", $count, $this->capacityString())
                );
            }
        }

        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = $this->next();
        }

        return $codes;
    }

    /**
     * Lowercase convenience: generates via next() and then lowercases.
     * Uniqueness is guaranteed against the uppercase canonical set.
     *
     * @throws PoolExhausted
     */
    public function nextLowercase(): string
    {
        return strtolower($this->next());
    }
}