# CrockfordRandom

A PHP library for generating random strings using Crockford Base32 encoding alphabet.

## Features

- Generates cryptographically secure random strings using PHP 8.2+'s `Random\Randomizer`
- Uses Crockford Base32 alphabet: `0123456789ABCDEFGHJKMNPQRSTVWXYZ`
- Excludes ambiguous characters (I, L, O, U) for better readability
- Type-safe with strict typing enabled
- Comprehensive error handling

## Requirements

- PHP 8.2 or higher
- `Random\Randomizer` extension (included in PHP 8.2+)

## Installation

```bash
composer require checkthiscloud/crockford-random
```

## Usage

```php
<?php
use App\Util\CrockfordRandom;

// Generate a random string of specified length
$randomString = CrockfordRandom::generate(10);
echo $randomString; // Example: "4G2KPQRST3"

// Generate empty string
$empty = CrockfordRandom::generate(0);
echo $empty; // ""

// Generate longer strings
$longString = CrockfordRandom::generate(32);
echo $longString; // Example: "8N2KPQRST34G2KPQRST34G2KPQRST3W"
```

## Error Handling

The library throws `ValueError` for invalid input:

```php
try {
    CrockfordRandom::generate(-1);
} catch (ValueError $e) {
    echo $e->getMessage(); // "Length must be non-negative"
}
```

## Testing

Run the included tests:

```bash
php run-tests.php
```

## Character Set

The library uses the Crockford Base32 alphabet which excludes ambiguous characters:

- **Included**: `0123456789ABCDEFGHJKMNPQRSTVWXYZ`
- **Excluded**: `I`, `L`, `O`, `U` (to avoid confusion with `1`, `1`, `0`, `V`)

## License

MIT License