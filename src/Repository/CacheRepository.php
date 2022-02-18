<?php

namespace Batenburg\CacheBundle\Repository;

use Closure;
use Batenburg\CacheBundle\Repository\Contract\CacheRepositoryInterface;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;

class CacheRepository implements CacheRepositoryInterface
{
    const DEFAULT_EXPIRES_AFTER_IN_SECONDS = 3600;

    /**
     * @var CacheItemPoolInterface
     */
    private $cacheAdapter;

    /**
     * @param CacheItemPoolInterface $cacheAdapter
     */
    public function __construct(CacheItemPoolInterface $cacheAdapter)
    {
        $this->cacheAdapter = $cacheAdapter;
    }

    /**
     * @param string $key
     * @return bool
     * @throws InvalidArgumentException
     */
    public function hasItem(string $key): bool
    {
        return $this->cacheAdapter->getItem($key)->isHit();
    }

    /**
     * @param string $key
     * @return bool
     * @throws InvalidArgumentException
     */
    public function missingItem(string $key): bool
    {
        return !$this->hasItem($key);
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return CacheItemInterface
     * @throws InvalidArgumentException
     */
    public function getItem(string $key, $default = null): CacheItemInterface
    {
        $item = $this->cacheAdapter->getItem($key);

        if ($this->needsToEscapeToDefault($item, $default)) {
            $item->set($this->resolveDefault($default));
        }

        return $item;
    }

    /**
     * @param string $key
     * @param callable $callback
     * @param int|null $expiresAfterInSeconds
     * @param bool $forceRefresh
     * @param array $arguments
     * @return CacheItemInterface
     * @throws InvalidArgumentException
     */
    public function rememberItem(
        string $key,
        callable $callback,
        ?int $expiresAfterInSeconds = null,
        bool $forceRefresh = false,
        ...$arguments
    ): CacheItemInterface {
        $item = $this->cacheAdapter->getItem($key);

        if (!$item->isHit() || $forceRefresh) {
            $item = $this->saveItem($key, $callback(...$arguments), $expiresAfterInSeconds);
        }

        return $item;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param int|null $expiresAfterInSeconds
     * @return CacheItemInterface
     * @throws InvalidArgumentException
     */
    public function saveItem(string $key, $value, ?int $expiresAfterInSeconds = null): CacheItemInterface
    {
        $item = $this->cacheAdapter->getItem($key);
        $item->set($value)
            ->expiresAfter($this->resolveExpiresAfter($expiresAfterInSeconds));

        $this->cacheAdapter->save($item);

        return $item;
    }

    /**
     * @param string $key
     * @return bool
     * @throws InvalidArgumentException
     */
    public function deleteItem(string $key): bool
    {
        return $this->cacheAdapter->deleteItem($key);
    }

    /**
     * @param mixed $default
     * @return mixed
     */
    private function resolveDefault($default)
    {
        return $default instanceof Closure ? $default() : $default;
    }

    /**
     * @param int|null $expiresAfterInSeconds
     * @return int
     */
    private function resolveExpiresAfter(?int $expiresAfterInSeconds = null): int
    {
        return $expiresAfterInSeconds ?: self::DEFAULT_EXPIRES_AFTER_IN_SECONDS;
    }

    /**
     * @param CacheItemInterface $item
     * @param mixed $default
     * @return bool
     */
    private function needsToEscapeToDefault(CacheItemInterface $item, $default = null): bool
    {
        return !$item->isHit()
            && !is_null($default);
    }
}
