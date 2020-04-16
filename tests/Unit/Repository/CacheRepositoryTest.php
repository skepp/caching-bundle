<?php

namespace Batenburg\CacheBundle\Test\Unit\Repository;

use Batenburg\CacheBundle\Repository\CacheRepository;
use Batenburg\CacheBundle\Repository\Contract\CacheRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\AdapterInterface;

/**
 * @covers \Batenburg\CacheBundle\Repository\CacheRepository
 */
class CacheRepositoryTest extends TestCase
{

    /**
     * @var MockObject|AdapterInterface
     */
    private $cacheAdapter;

    /**
     * @var MockObject|CacheItemInterface
     */
    private $cachedItem;

    /**
     * @var CacheRepository
     */
    private $cacheRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cacheAdapter    = $this->createMock(AdapterInterface::class);
        $this->cachedItem      = $this->createMock(CacheItemInterface::class);
        $this->cacheRepository = new CacheRepository($this->cacheAdapter);
    }

    public function testACacheRepositoryImplementTheInterface(): void
    {
        $this->assertInstanceOf(CacheRepositoryInterface::class, $this->cacheRepository);
    }

    /**
     * @dataProvider hasItemScenarioProvider
     * @covers \Batenburg\CacheBundle\Repository\CacheRepository::hasItem
     * @param bool $expected
     * @param string $key
     * @param bool $isCacheHit
     * @throws InvalidArgumentException
     */
    public function testHasItem(
        bool $expected,
        string $key,
        bool $isCacheHit
    ): void {
        // Setup
        $this->mockCacheItemIsHit($key, $isCacheHit);
        // Execute
        $result = $this->cacheRepository->hasItem($key);
        // Validate
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider missingItemScenarioProvider
     * @covers \Batenburg\CacheBundle\Repository\CacheRepository::missingItem
     * @param bool $expected
     * @param string $key
     * @param bool $isCacheHit
     * @throws InvalidArgumentException
     */
    public function testMissingItem(
        bool $expected,
        string $key,
        bool $isCacheHit
    ): void {
        // Setup
        $this->mockCacheItemIsHit($key, $isCacheHit);
        // Execute
        $result = $this->cacheRepository->missingItem($key);
        // Validate
        $this->assertEquals($expected, $result);
    }

    /**
     * @covers \Batenburg\CacheBundle\Repository\CacheRepository::getItem
     * @throws InvalidArgumentException
     */
    public function testGetItemResolvesTheItem(): void
    {
        // Setup
        $key = 'test.key';
        $this->mockCacheItemIsHit($key, true);
        // Expectation
        $this->cachedItem->expects($this->never())
            ->method('set');
        $this->cacheAdapter->expects($this->never())
            ->method('save');
        // Execute
        $result = $this->cacheRepository->getItem($key);
        // Validate
        $this->assertInstanceOf(CacheItemInterface::class, $result);
    }

    /**
     * @covers \Batenburg\CacheBundle\Repository\CacheRepository::getItem
     * @throws InvalidArgumentException
     */
    public function testGetItemResolvesTheDefaultIntoTheItem(): void
    {
        // Setup
        $key         = 'test.key';
        $cachingText = 'test cache text';
        $this->mockCacheItemIsHit($key, false);
        // Expectation
        $this->cachedItem->expects($this->once())
            ->method('set')
            ->with($cachingText)
            ->willReturn($this->cachedItem);
        $this->cacheAdapter->expects($this->never())
            ->method('save');
        // Execute
        $result = $this->cacheRepository->getItem($key, $cachingText);
        // Validate
        $this->assertInstanceOf(CacheItemInterface::class, $result);
    }

    /**
     * @covers \Batenburg\CacheBundle\Repository\CacheRepository::getItem
     * @throws InvalidArgumentException
     */
    public function testGetItemReturnsTheItemWithoutResolvingDefaultWhenDefaultNull(): void
    {
        // Setup
        $key = 'test.key';
        $this->mockCacheItemIsHit($key, false);
        // Expectation
        $this->cachedItem->expects($this->never())
            ->method('set');
        $this->cacheAdapter->expects($this->never())
            ->method('save');
        // Execute
        $result = $this->cacheRepository->getItem($key);
        // Validate
        $this->assertInstanceOf(CacheItemInterface::class, $result);
    }

    /**
     * @covers \Batenburg\CacheBundle\Repository\CacheRepository::rememberItem
     * @throws InvalidArgumentException
     */
    public function testWhenTheItemIsRetrievedFromTheCacheItWillNotSaveToTheCache(): void
    {
        // Setup
        $key     = 'test.key';
        $closure = function () {
            return 'item';
        };
        $this->mockCacheItemIsHit($key, true);
        // Expectations
        $this->cachedItem->expects($this->never())
            ->method('set');
        $this->cacheAdapter->expects($this->never())
            ->method('save');
        // Execute
        $result = $this->cacheRepository->rememberItem($key, $closure);
        // Validate
        $this->assertInstanceOf(CacheItemInterface::class, $result);
    }

    /**
     * @covers \Batenburg\CacheBundle\Repository\CacheRepository::rememberItem
     * @throws InvalidArgumentException
     */
    public function testWhenAnItemIsNotInCacheTheRememberItemWillSaveAndReturnIt(): void
    {
        // Setup
        $key      = 'test.key';
        $expected = 'expected result';
        $closure  = function () use ($expected) {
            return $expected;
        };
        $this->mockCacheItemIsHit($key, false);
        // Expectations
        $this->expectedItemSave($key, 1, $expected, 3600);
        // Execute
        $result = $this->cacheRepository->rememberItem($key, $closure);
        // Validate
        $this->assertInstanceOf(CacheItemInterface::class, $result);
    }

    /**
     * @covers \Batenburg\CacheBundle\Repository\CacheRepository::rememberItem
     * @throws InvalidArgumentException
     */
    public function testAnItemIsRememberedWithArguments(): void
    {
        // Setup
        $id      = 999;
        $key     = 'test.key';
        $closure = [$this, 'fakeFindById'];
        $this->mockCacheItemIsHit($key, false);
        // Expectations
        $this->expectedItemSave($key, 1, $id, 3600);
        // Execute
        $result = $this->cacheRepository->rememberItem($key, $closure, null, false, $id);
        // Validate
        $this->assertInstanceOf(CacheItemInterface::class, $result);
    }

    /**
     * @covers \Batenburg\CacheBundle\Repository\CacheRepository::rememberItem
     * @throws InvalidArgumentException
     */
    public function testAnItemIsRememberedWithMultipleArguments(): void
    {
        // Setup
        $id       = 999;
        $argument = 'text';
        $key      = 'test.key';
        $closure  = [$this, 'fakeFindByIdWithArgument'];
        $this->mockCacheItemIsHit($key, false);
        // Expectations
        $this->expectedItemSave($key, 1, $id . $argument, 3600);
        // Execute
        $result = $this->cacheRepository->rememberItem(
            $key,
            $closure,
            null,
            false,
            $id,
            $argument
        );
        // Validate
        $this->assertInstanceOf(CacheItemInterface::class, $result);
    }

    /**
     * @covers \Batenburg\CacheBundle\Repository\CacheRepository::rememberItem
     * @throws InvalidArgumentException
     */
    public function testARememberItemCanBeStoredWithACustomExpiresAfter(): void
    {
        // Setup
        $key      = 'test.key';
        $expected = 'expected result';
        $closure  = function () use ($expected) {
            return $expected;
        };
        $expiresAfterInSeconds = 600;
        $this->mockCacheItemIsHit($key, false);
        // Expectations
        $this->expectedItemSave($key, 1, $expected, $expiresAfterInSeconds);
        // Execute
        $result = $this->cacheRepository->rememberItem($key, $closure, $expiresAfterInSeconds);
        // Validate
        $this->assertInstanceOf(CacheItemInterface::class, $result);
    }

    /**
     * @covers \Batenburg\CacheBundle\Repository\CacheRepository::rememberItem
     * @throws InvalidArgumentException
     */
    public function testARememberItemCanBeStoredWithACallable(): void
    {
        // Setup
        $key      = 'test.key';
        $callable = [$this, 'fakeCallback'];
        $this->mockCacheItemIsHit($key, false);
        // Expectations
        $this->expectedItemSave($key, 1, 'fake result', 3600);
        // Execute
        $result = $this->cacheRepository->rememberItem($key, $callable);
        // Validate
        $this->assertInstanceOf(CacheItemInterface::class, $result);
    }

    /**
     * @covers \Batenburg\CacheBundle\Repository\CacheRepository::rememberItem
     * @throws InvalidArgumentException
     */
    public function testARememberItemCanForcedToBeRefreshed(): void
    {
        // Setup
        $key      = 'test.key';
        $callable = [$this, 'fakeCallback'];
        $this->mockCacheItemIsHit($key, true);
        // Expectations
        $this->expectedItemSave($key, 1, 'fake result', 3600);
        // Execute
        $result = $this->cacheRepository->rememberItem($key, $callable, 3600, true);
        // Validate
        $this->assertInstanceOf(CacheItemInterface::class, $result);
    }


    /**
     * @covers \Batenburg\CacheBundle\Repository\CacheRepository::saveItem
     * @throws InvalidArgumentException
     */
    public function testSaveItemWithDefaultExpiresAfter(): void
    {
        // Setup
        $key   = 'test.key';
        $value = 'the value to cache';
        // Expectations
        $this->expectedItemSave($key, 0, $value, 3600);
        // Execute
        $this->cacheRepository->saveItem($key, $value);
    }

    /**
     * @covers \Batenburg\CacheBundle\Repository\CacheRepository::saveItem
     * @throws InvalidArgumentException
     */
    public function testSaveItemWithAnAdjustedExpiresAfter(): void
    {
        // Setup
        $key                   = 'test.key';
        $value                 = 'the value to cache';
        $expiresAfterInSeconds = 600;
        // Expectations
        $this->expectedItemSave($key, 0, $value, $expiresAfterInSeconds);
        // Execute
        $this->cacheRepository->saveItem($key, $value, $expiresAfterInSeconds);
    }

    /**
     * @covers \Batenburg\CacheBundle\Repository\CacheRepository::deleteItem
     * @throws InvalidArgumentException
     */
    public function testDeleteItem(): void
    {
        //Setup
        $key = 'test.key';
        // Expectations
        $this->cacheAdapter->expects($this->once())
            ->method('deleteItem')
            ->with($key)
            ->willReturn(true);
        // Execute
        $result = $this->cacheRepository->deleteItem($key);
        // Validate
        $this->assertTrue($result);
    }

    /**
     * @return array
     */
    public function hasItemScenarioProvider(): array
    {
        return [
            'when an item is hit' => [
                true,
                'test.key',
                true
            ],
            'when an item is not hit' => [
                false,
                'test.key',
                false
            ],
        ];
    }

    /**
     * @return array
     */
    public function missingItemScenarioProvider(): array
    {
        return [
            'when an item is hit' => [
                true,
                'test.key',
                false
            ],
            'when an item is not hit' => [
                false,
                'test.key',
                true
            ],
        ];
    }

    /**
     * @return string
     */
    public function fakeCallback(): string
    {
        return 'fake result';
    }

    /**
     * @param int $id
     * @return int
     */
    public function fakeFindById(int $id): int
    {
        return $id;
    }

    /**
     * @param int $id
     * @param string $argument
     * @return string
     */
    public function fakeFindByIdWithArgument(int $id, string $argument): string
    {
        return $id . $argument;
    }

    /**
     * @param string $key
     * @param bool $isCacheHit
     */
    private function mockCacheItemIsHit(string $key, bool $isCacheHit): void
    {
        $this->cacheAdapter->expects($this->at(0))
            ->method('getItem')
            ->with($key)
            ->willReturn($this->cachedItem);
        $this->cachedItem->expects($this->once())
            ->method('isHit')
            ->willReturn($isCacheHit);
    }

    /**
     * @param string $key
     * @param int $at
     * @param string $expectedValue
     * @param int $expectedExpiresAfterInSeconds
     */
    private function expectedItemSave(
        string $key,
        int $at,
        string $expectedValue,
        int $expectedExpiresAfterInSeconds
    ): void {
        $this->cacheAdapter->expects($this->at($at))
            ->method('getItem')
            ->with($key)
            ->willReturn($this->cachedItem);
        $this->cachedItem->expects($this->once())
            ->method('set')
            ->with($expectedValue)
            ->willReturn($this->cachedItem);
        $this->cachedItem->expects($this->once())
            ->method('expiresAfter')
            ->with($expectedExpiresAfterInSeconds)
            ->willReturn($this->cachedItem);
        $this->cacheAdapter->expects($this->once())
            ->method('save')
            ->willReturn(true);
    }
}
