<?php

namespace Batenburg\CacheBundle\Test\Unit;

use Batenburg\CacheBundle\CacheBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @covers \Batenburg\CacheBundle\CacheBundle
 */
class CacheBundleTest extends TestCase
{

    /**
     * @covers \Batenburg\CacheBundle\CacheBundle
     */
    public function testCacheBundle(): void
    {
        // Execute
        $bundle = new CacheBundle();
        // Validate
        $this->assertInstanceOf(Bundle::class, $bundle);
        $this->assertInstanceOf(CacheBundle::class, $bundle);
        $this->assertSame('CacheBundle', $bundle->getName());
    }
}
