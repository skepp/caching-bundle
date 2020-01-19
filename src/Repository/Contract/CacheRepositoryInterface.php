<?php

namespace Batenburg\CacheBundle\Repository\Contract;

use Psr\Cache\CacheItemInterface;

interface CacheRepositoryInterface
{

    /**
     * @param string $key
     * @return bool
     */
    public function hasItem(string $key): bool;

    /**
     * @param string $key
     * @return bool
     */
    public function missingItem(string $key): bool;

    /**
     * @param string $key
     * @param mixed $default
     * @return CacheItemInterface
     */
    public function getItem(string $key, $default = null): CacheItemInterface;

    /**
     * @param string $key
     * @param callable $callback
     * @param int|null $expiresAfterInSeconds
     * @param array $arguments
     * @return CacheItemInterface
     */
    public function rememberItem(
        string $key,
        callable $callback,
        ?int $expiresAfterInSeconds = null,
        ...$arguments
    ): CacheItemInterface;

    /**
     * @param string $key
     * @param mixed $value
     * @param int|null $expiresAfterInSeconds
     * @return CacheItemInterface
     */
    public function saveItem(string $key, $value, ?int $expiresAfterInSeconds = null): CacheItemInterface;

    /**
     * @param string $key
     * @return bool
     */
    public function deleteItem(string $key): bool;
}
