<?php

namespace Batenburg\CacheBundle\Test\Integration;

use Batenburg\CacheBundle\CacheBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\Kernel;

class TestKernel extends Kernel
{

    /**
     * @return array|iterable|BundleInterface[]
     */
    public function registerBundles()
    {
        return [
            new CacheBundle()
        ];
    }

    /**
     * @param LoaderInterface $loader
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
    }
}
