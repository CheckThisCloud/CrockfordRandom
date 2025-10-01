# GitHub Copilot Instructions for CrockfordRandom

## Project Overview

CrockfordRandom is a PHP library for generating cryptographically secure random strings using the Crockford Base32 encoding alphabet. The library is designed for simplicity, security, and type safety.

## Key Features

- Generates cryptographically secure random strings using PHP 8.3+'s `Random\Randomizer`
- Uses Crockford Base32 alphabet: `0123456789ABCDEFGHJKMNPQRSTVWXYZ`
- Excludes ambiguous characters (I, L, O, U) for better readability
- Type-safe with strict typing enabled
- Comprehensive error handling

## Code Style and Standards

### PHP Version
- Target PHP 8.3+
- Always use `declare(strict_types=1);` at the top of PHP files

### Type Safety
- Use strict type declarations for all parameters and return types
- Prefer final classes when inheritance is not needed
- Use readonly properties where applicable

### Naming Conventions
- Use PSR-4 autoloading standards
- Namespace: `CheckThisCloud\CrockfordRandom`
- Class names in PascalCase
- Method names in camelCase
- Constants in UPPER_SNAKE_CASE

### Error Handling
- Use `ValueError` for invalid input parameters
- Provide clear, descriptive error messages
- Never silently fail

## Testing Requirements

### PHPUnit
- All code changes must include tests
- Use PHPUnit 12.3+
- Run tests with: `composer test`
- Test coverage should include:
  - Happy path scenarios
  - Edge cases (zero length, large lengths)
  - Error conditions (negative lengths)
  - Character validation

### Test Patterns
- Use descriptive test method names that explain what is being tested
- Use type hints in test methods
- Each test should test one specific behavior
- Use data providers for testing multiple scenarios

### Running Tests
```bash
composer test           # Run PHPUnit tests
composer phpstan        # Run static analysis
composer check          # Run both
```

## Static Analysis

### PHPStan
- Level: max (strictest level)
- Includes strict rules from phpstan-strict-rules
- All code must pass PHPStan analysis
- Run with: `composer phpstan`

## Architecture

### Core Components
- `CrockfordRandom`: Main class with static `generate()` method
- Single responsibility: Generate random strings using Crockford Base32 alphabet

### Character Set
- **Alphabet**: `0123456789ABCDEFGHJKMNPQRSTVWXYZ` (32 characters)
- **Excluded**: I, L, O, U (avoid confusion with 1, 1, 0, V)
- Never modify the alphabet without updating documentation and tests

### Security
- Use `Random\Randomizer` for cryptographic security
- Use `getBytesFromString()` method for character selection
- Never use `rand()`, `mt_rand()`, or other non-cryptographic PRNGs

## Development Workflow

### Adding New Features
1. Write tests first (TDD approach)
2. Implement the minimal code to pass tests
3. Run static analysis to ensure type safety
4. Update documentation if needed

### Code Review Checklist
- [ ] Strict types declared
- [ ] All parameters and return types have type hints
- [ ] Error cases handled with appropriate exceptions
- [ ] Tests added for new functionality
- [ ] PHPStan analysis passes
- [ ] Code follows existing patterns
- [ ] Documentation updated if needed

## Common Patterns

### String Generation
```php
// Always use Random\Randomizer
$randomizer = new Randomizer();
return $randomizer->getBytesFromString(self::ALPHABET, $length);
```


## Dependencies

### Production
- PHP: ^8.3
- math/brick
- uses built-in Random\Randomizer

### Development
- phpunit/phpunit: ^12.3
- phpstan/phpstan: ^2.1
- phpstan/phpstan-strict-rules: ^2.0

## Documentation

### Code Documentation
- Use PHPDoc for public methods
- Include parameter descriptions and return type documentation
- Document thrown exceptions

### README Updates
- Update examples if API changes
- Keep character set documentation accurate
- Update version requirements if needed

## Performance Considerations

- String generation is O(n) where n is the requested length
- No special optimization needed for typical use cases (< 10,000 characters)
- `Random\Randomizer` is already optimized for cryptographic security

## Security Considerations

- Never log or expose generated random strings in debug output
- Ensure generated strings maintain cryptographic randomness
- Don't cache or reuse Randomizer instances in ways that reduce entropy
- Validate all user inputs before processing

## Best Practices for Contributions

1. **Keep it simple**: This is a focused library with a single responsibility
2. **Maintain backward compatibility**: Avoid breaking changes
3. **Test thoroughly**: Include edge cases and error conditions
4. **Follow existing patterns**: Maintain consistency with existing code
5. **Document changes**: Update README and code comments as needed


## Questions?

If you're unsure about:
- Character set changes → Don't change it (it's standardized)
- Adding dependencies → Avoid unless absolutely necessary
- Breaking changes → Discuss with maintainers first
- Security implications → Consult with maintainers
