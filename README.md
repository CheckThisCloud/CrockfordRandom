# CrockfordRandom

A PHP library implementing Douglas Crockford's Random Number Generator algorithm.

## Requirements

- PHP 8.3 or higher

## Installation

Install via Composer:

```bash
composer require checkthiscloud/crockford-random
```

## Usage

```php
<?php

use CheckThisCloud\CrockfordRandom\CrockfordRandom;

$generator = new CrockfordRandom();
$randomNumber = $generator->random(); // Returns a float between 0 and 1
```

## Development

### Install Dependencies

```bash
composer install
```

### Run Tests

```bash
composer test
```

### Run Static Analysis

```bash
composer phpstan
```

### Run All Checks

```bash
composer check
```