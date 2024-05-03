<?php

namespace ryunosuke\SimpleCache\Contract;

use DateInterval;

interface HashableInterface
{
    /**
     * Fetches a value from the hashed key.
     *
     * @param string $key The unique key of this item in the cache.
     * @param mixed $default Default value to return if the key does not exist.
     * @return mixed The value of the item from the cache, or $default in case of cache miss.
     */
    public function getByHash(string $key, mixed $default = null);

    /**
     * Persists data in the hashed key, uniquely referenced by a key with an optional expiration TTL time.
     *
     * @param string $key The key of the item to store.
     * @param mixed $value The value of the item to store, must be serializable.
     * @param null|int|DateInterval $ttl Optional. The TTL value of this item. If no value is sent and
     *                                      the driver supports TTL then the library may set a default value
     *                                      for it or let the driver take care of that.
     * @return bool True on success and false on failure.
     */
    public function setByHash(string $key, mixed $value, null|int|DateInterval $ttl = null): bool;

    /**
     * Delete an item from the cache by its unique hashed key.
     *
     * @param string $key The unique cache key of the item to delete.
     * @return bool True if the item was successfully removed. False if there was an error.
     */
    public function deleteByHash(string $key): bool;

    /**
     * Obtains multiple cache items by their unique hashed keys.
     *
     * @param iterable $keys A list of keys that can obtained in a single operation.
     * @param mixed $default Default value to return for keys that do not exist.
     * @return iterable A list of key => value pairs. Cache keys that do not exist or are stale will have $default as value.
     */
    public function getMultipleByHash(iterable $keys, mixed $default = null): iterable;

    /**
     * Persists a set of hashed key => value pairs in the cache, with an optional TTL.
     *
     * @param iterable $values A list of key => value pairs for a multiple-set operation.
     * @param null|int|DateInterval $ttl Optional. The TTL value of this item. If no value is sent and
     *                                       the driver supports TTL then the library may set a default value
     *                                       for it or let the driver take care of that.
     * @return bool True on success and false on failure.
     */
    public function setMultipleByHash(iterable $values, null|int|DateInterval $ttl = null): bool;

    /**
     * Deletes multiple hashed cache items in a single operation.
     *
     * @param iterable $keys A list of string-based keys to be deleted.
     * @return bool True if the items were successfully removed. False if there was an error.
     */
    public function deleteMultipleByHash(iterable $keys): bool;

    /**
     * Determines whether an item is present in the hashed key.
     *
     * NOTE: It is recommended that has() is only to be used for cache warming type purposes
     * and not to be used within your live applications operations for get/set, as this method
     * is subject to a race condition where your has() will return true and immediately after,
     * another script can remove it making the state of your app out of date.
     *
     * @param string $key The cache item key.
     * @return bool
     */
    public function hasByHash(string $key): bool;
}
