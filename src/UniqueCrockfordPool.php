<?php

declare(strict_types=1);

namespace CheckThisCloud\CrockfordRandom;

use Brick\Math\BigInteger;
use CheckThisCloud\CrockfordRandom\Exception\PoolExhausted;

final class UniqueCrockfordPool
{
    /** @var array<string, true> Used as a set for O(1) lookups. */
    private array $storage;


    /**
     * Create a pool that yields unique Crockford Base32 codes of fixed $length.
     */
    public function __construct(private readonly int $length)
    {
        if ($length <= 0) {
            throw new \InvalidArgumentException('Length must be positive.');
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
        if ($this->issuedCount() >= $this->capacityInt()) {
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
     * @return int May overflow PHP int for large lengths; document limits.
     */
    public function capacityInt(): int
    {
        return (int) pow(32, $this->length);
    }

    public function capacityBig(): BigInteger
    {
        return BigInteger::of(32)->power($this->length);
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
            throw new \InvalidArgumentException('Count must be positive.');
        }
        if ($this->issuedCount() + $count > $this->capacityInt()) {
            throw new PoolExhausted(
                sprintf("Reserving %d codes would exceed pool capacity of %d.", $count, $this->capacityInt())
            );
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