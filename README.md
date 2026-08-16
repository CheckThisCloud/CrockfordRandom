# CrockfordRandom

[![CI](https://github.com/CheckThisCloud/CrockfordRandom/actions/workflows/ci.yml/badge.svg)](https://github.com/CheckThisCloud/CrockfordRandom/actions/workflows/ci.yml)
[![codecov](https://codecov.io/gh/CheckThisCloud/CrockfordRandom/branch/main/graph/badge.svg)](https://codecov.io/gh/CheckThisCloud/CrockfordRandom)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.3-blue.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Latest Version](https://img.shields.io/packagist/v/checkthiscloud/crockford-random.svg)](https://packagist.org/packages/checkthiscloud/crockford-random)
[![Total Downloads](https://img.shields.io/packagist/dt/checkthiscloud/crockford-random.svg)](https://packagist.org/packages/checkthiscloud/crockford-random)

A PHP library for generating random strings using Crockford Base32 encoding alphabet.

## Features

- Generates cryptographically secure random strings using PHP 8.3+'s `Random\Randomizer`
- Uses Crockford Base32 alphabet: `0123456789ABCDEFGHJKMNPQRSTVWXYZ`
- Excludes ambiguous characters (I, L, O, U) for better readability
- Exclude specific codes from generation, exactly rather than by retrying
- Type-safe with strict typing enabled
- Comprehensive error handling
- Optional `brick/math` dependency for large unique pools (> 1.15 quintillion codes)

## Requirements

- PHP 8.3 or higher
- `Random\Randomizer` extension (included in PHP 8.3+)

### Optional Dependencies

- `brick/math` (^0.8 || ^0.9 || ^0.10 || ^0.11 || ^0.12) - Required only for `UniqueCrockfordPool` with lengths > 12. For most use cases (pool lengths 1-12, which support up to 1.15 quintillion unique codes), native PHP integers are sufficient.

## Installation

```bash
composer require checkthiscloud/crockford-random
```

## Usage

### Basic Random String Generation

```php
<?php
use CheckThisCloud\CrockfordRandom\CrockfordRandom;

// Generate a random string of specified length
$randomString = CrockfordRandom::generate(10);
echo $randomString; // Example: "4G2KPQRST3"

// Lowercase variant
$lower = CrockfordRandom::generateLowercase(10);
echo $lower; // Example: "4g2kpqrst3"

// Generate longer strings
$longString = CrockfordRandom::generate(32);
echo $longString; // Example: "8N2KPQRST34G2KPQRST34G2KPQRST3W"

// Length must be positive
CrockfordRandom::generate(0); // throws ValueError: "Length must be positive"
```

### Excluding Codes

Pass codes that must never be returned. Matching is case-insensitive:

```php
$taken = ['4G2KPQRST3', 'ZZZZZZZZZZ'];

$code = CrockfordRandom::generate(10, $taken);       // never one of $taken
$code = CrockfordRandom::generateLowercase(10, $taken);
```

Selection is exact rather than retry-based: as long as one non-excluded code of the
requested length exists, `generate()` returns it. It throws only when every code of
that length is excluded:

```php
CrockfordRandom::generate(1, str_split(CrockfordRandom::ALPHABET));
// throws RuntimeException: "Every code of length 1 is excluded."
```

Entries the generator could never produce — the wrong length, or characters outside
the alphabet such as `I`, `L`, `O` and `U` — are ignored.

The `$maxAttempts` parameter applies only above length 12, where codes are drawn by
rejection sampling. At or below length 12 selection is exact and the parameter is ignored.

### Unique Code Pool (Experimental)

The `UniqueCrockfordPool` class generates unique Crockford Base32 codes of a fixed length, ensuring no duplicates within a pool instance:

```php
<?php
use CheckThisCloud\CrockfordRandom\UniqueCrockfordPool;

// Create a pool for 6-character codes (capacity: 1,073,741,824 unique codes)
$pool = new UniqueCrockfordPool(6);

// Get the next unique code
$code1 = $pool->next();  // Example: "A3F2K9"
$code2 = $pool->next();  // Example: "7Y4MXP" (guaranteed different from code1)

// Reserve multiple codes at once
$codes = $pool->reserve(100);  // Returns array of 100 unique codes

// Check pool status
echo $pool->issuedCount();  // 102 (2 + 100)
echo $pool->capacityInt();     // 1073741824
echo $pool->remaining();       // 1073741722
echo $pool->remainingString(); // "1073741722" — works at any length

// Check if a code was issued
$pool->hasIssued($code1);  // true
$pool->hasIssued('ZZZZZZ');  // false

// Reset the pool (useful for testing)
$pool->reset();
echo $pool->issuedCount();  // 0
```

#### Excluding Codes From a Pool

Codes can be excluded up front (for example, codes already issued elsewhere) or added later:

```php
$pool = new UniqueCrockfordPool(6, ['A3F2K9', 'ZZZZZZ']);
$pool->exclude(['7Y4MXP']);

$pool->isExcluded('a3f2k9');  // true — matching is case-insensitive
$pool->excludedCount();       // 3
$pool->remaining();           // capacity - issued - excluded
```

Excluded codes count against the pool's capacity and survive `reset()`. Every code must
match the pool's length and contain only Crockford Base32 characters — otherwise
`InvalidLength` or `InvalidCode` is thrown and the call applies nothing. Excluding a code
the pool already issued is a no-op, so it is never counted against capacity twice.

#### Pool Capacity Limits

The pool capacity is `32^length`:
- Length 1-12: Supported without `brick/math` (up to 1.15 quintillion codes at length 12)
- Length 13+: Requires `brick/math` library

`capacityInt()` and `remaining()` return native integers and therefore throw above length 12.
`capacityString()` and `remainingString()` work at every length, and reach for `brick/math`
only above length 12 — exactly where the constructor already requires it.

```bash
# Install brick/math for large pools (optional)
composer require brick/math
```

If you try to create a pool with length > 12 without `brick/math`, you'll get a clear error message:

```php
// Without brick/math installed
$pool = new UniqueCrockfordPool(13);
// Throws: InvalidLength: "Length 13 requires brick/math library. 
//         Install it with: composer require brick/math. 
//         For lengths up to 12, brick/math is not required."
```

## Error Handling

The library throws `ValueError` for invalid input:

```php
try {
    CrockfordRandom::generate(-1);
} catch (ValueError $e) {
    echo $e->getMessage(); // "Length must be positive"
}
```

| Exception | Extends | Thrown when |
| --- | --- | --- |
| `ValueError` | — | `generate()` is given a non-positive length |
| `RuntimeException` | — | every code of the requested length is excluded |
| `Exception\InvalidLength` | `ValueError` | a pool length is non-positive, an excluded code is the wrong length, or capacity exceeds PHP's integer limits |
| `Exception\InvalidCode` | `ValueError` | an excluded code contains characters outside the alphabet |
| `Exception\PoolExhausted` | `RuntimeException` | a pool has issued every code of its length |

## Testing

Run the tests with PHPUnit:

```bash
composer test
# or
./vendor/bin/phpunit
```

## Character Set

The library uses the Crockford Base32 alphabet which excludes ambiguous characters:

- **Included**: `0123456789ABCDEFGHJKMNPQRSTVWXYZ`
- **Excluded**: `I`, `L`, `O`, `U` (to avoid confusion with `1`, `1`, `0`, `V`)

## License

MIT License