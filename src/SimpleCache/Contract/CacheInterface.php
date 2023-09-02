<?php

namespace ryunosuke\SimpleCache\Contract;

/**
 * override return type of \Psr\SimpleCache\CacheInterface
 *
 * @see https://www.php.net/manual/language.oop5.variance.php
 * - child's method to return a more specific type than the return type of its parent's method
 * - parameter type to be less specific in a child method, than that of its parent
 *
 * this interface will not be needed once php8 is supported
 */
interface CacheInterface extends \Psr\SimpleCache\CacheInterface
{
    /** @inheritdoc */
    public function get($key, $default = null);

    /** @inheritdoc */
    public function set($key, $value, $ttl = null): bool;

    /** @inheritdoc */
    public function delete($key): bool;

    /** @inheritdoc */
    public function clear(): bool;

    /** @inheritdoc */
    public function getMultiple($keys, $default = null): iterable;

    /** @inheritdoc */
    public function setMultiple($values, $ttl = null): bool;

    /** @inheritdoc */
    public function deleteMultiple($keys): bool;

    /** @inheritdoc */
    public function has($key): bool;
}
