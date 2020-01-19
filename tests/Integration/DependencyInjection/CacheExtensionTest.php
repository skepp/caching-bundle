<?php

namespace Batenburg\CacheBundle\Test\Integration\DependencyInjection;

use Batenburg\CacheBundle\DependencyInjection\CacheExtension;
use Batenburg\CacheBundle\Repository\CacheRepository;
use Batenburg\CacheBundle\Repository\Contract\CacheRepositoryInterface;
use Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

/**
 * @covers \Batenburg\CacheBundle\DependencyInjection\CacheExtension
 */
class CacheExtensionTest extends TestCase
{
    /**
     * @covers \Batenburg\CacheBundle\DependencyInjection\CacheExtension
     * @throws Exception
     */
    public function testServicesWiring(): void
    {
        // Setup
        $container = $this->getContainer();
        $extension = new CacheExtension();
        $container->registerExtension($extension);
        $extension->load([], $container);
        // Validate
        $this->assertTrue($container->hasDefinition('batenburg.cache_bundle.repository.cache_repository'));
        $this->assertSame(
            CacheRepository::class,
            $container->getDefinition('batenburg.cache_bundle.repository.cache_repository')->getClass()
        );
        $this->assertFalse(
            $container->getDefinition('batenburg.cache_bundle.repository.cache_repository')->isPublic()
        );
        $this->assertTrue($container->hasAlias(CacheRepositoryInterface::class));
        $this->assertSame(
            'batenburg.cache_bundle.repository.cache_repository',
            $container->getAlias(CacheRepositoryInterface::class)->__toString()
        );
        $this->assertTrue(
            $container->getAlias(CacheRepositoryInterface::class)->isPublic()
        );
    }

    /**
     * @return ContainerBuilder
     */
    private function getContainer() : ContainerBuilder
    {
        $container = new ContainerBuilder(new ParameterBag([
            'kernel.name' => 'app',
            'kernel.debug' => false,
            'kernel.cache_dir' => sys_get_temp_dir(),
            'kernel.environment' => 'test',
            'kernel.root_dir' => __DIR__ . '/../../../',
        ]));

        $container->setDefinition(
            'cache.system',
            (new Definition(ArrayAdapter::class))->setPublic(true)
        );
        $container->setDefinition(
            'cache.app',
            (new Definition(ArrayAdapter::class))->setPublic(true)
        );
        $container->setDefinition(
            'my_pool',
            (new Definition(ArrayAdapter::class))->setPublic(true)
        );

        return $container;
    }
}


