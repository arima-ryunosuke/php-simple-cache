<?php

namespace ryunosuke\SimpleCache\Contract;

use DateInterval;

interface FetchableInterface
{
    /**
     * get or set if not exists
     *
     * @param string $key key
     * @param callable $provider value provider
     * @param null|int|DateInterval $ttl time to live
     * @return mixed cache or provider's return
     */
    public function fetch(string $key, callable $provider, null|int|DateInterval $ttl = null);

    /**
     * @param callable[] $providers values provider
     * @param null|int|DateInterval $ttl $ttl
     * @return array caches or provider's returns
     */
    public function fetchMultiple(iterable $providers, null|int|DateInterval $ttl = null): iterable;
}
