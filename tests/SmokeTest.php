<?php

namespace Kematjaya\BaseControllerBundle\Tests;

use PHPUnit\Framework\TestCase;

class SmokeTest extends TestCase
{
    public function testBundleClassExists(): void
    {
        $this->assertTrue(class_exists(\Kematjaya\BaseControllerBundle\BaseControllerBundle::class));
    }
}
