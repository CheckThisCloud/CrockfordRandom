#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Simple test runner for CrockfordRandom
 */

echo "CrockfordRandom Test Runner\n";
echo "===========================\n\n";

// Run the unit tests
require_once __DIR__ . '/tests/Unit/Util/CrockfordRandomTest.php';