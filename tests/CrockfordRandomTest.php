<?php

declare(strict_types=1);

namespace CheckThisCloud\CrockfordRandom\Tests;

use CheckThisCloud\CrockfordRandom\CrockfordRandom;
use PHPUnit\Framework\TestCase;

class CrockfordRandomTest extends TestCase
{
    public function testCanInstantiate(): void
    {
        $generator = new CrockfordRandom();
        
        $this->assertInstanceOf(CrockfordRandom::class, $generator);
    }
    
    public function testRandomReturnsFloat(): void
    {
        $generator = new CrockfordRandom();
        $result = $generator->random();
        
        $this->assertIsFloat($result);
        $this->assertGreaterThanOrEqual(0.0, $result);
        $this->assertLessThanOrEqual(1.0, $result);
    }
}