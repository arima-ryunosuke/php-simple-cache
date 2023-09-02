<?php

namespace ryunosuke\SimpleCache\Contract;

trait SingleTrait
{
    public function get($key, $default = null)
    {
        return $this->getMultiple([$key], $default)[$key];
    }

    public function set($key, $value, $ttl = null): bool
    {
        return $this->setMultiple([$key => $value], $ttl);
    }

    public function delete($key): bool
    {
        return $this->deleteMultiple([$key]);
    }

    public function fetch($key, $provider, $ttl = null)
    {
        return $this->fetchMultiple([$key => $provider], $ttl);
    }
}
