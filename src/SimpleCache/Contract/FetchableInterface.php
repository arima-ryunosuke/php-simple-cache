<?php

namespace ryunosuke\SimpleCache\Contract;

interface FetchableInterface
{
    /**
     * get or set if not exists
     *
     * @param string $key key
     * @param callable $provider value provider
     * @param ?int $ttl time to live
     * @return mixed cache or provider's return
     */
    public function fetch($key, $provider, $ttl = null);

    /**
     * @param callable[] $providers values provider
     * @param ?int $ttl $ttl
     * @return array caches or provider's returns
     */
    public function fetchMultiple(iterable $providers, $ttl = null);
}
