<?php

namespace ryunosuke\SimpleCache\Contract;

trait MultipleTrait
{
    public function getMultiple($keys, $default = null): iterable
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }
        return $result;
    }

    public function setMultiple($values, $ttl = null): bool
    {
        $result = true;
        foreach ($values as $key => $value) {
            $result = $this->set($key, $value, $ttl) && $result;
        }
        return $result;
    }

    public function deleteMultiple($keys): bool
    {
        $result = true;
        foreach ($keys as $key) {
            $result = $this->delete($key) && $result;
        }
        return $result;
    }

    public function fetchMultiple($providers, $ttl = null): iterable
    {
        $result = [];
        foreach ($providers as $key => $provider) {
            $result[$key] = $this->fetch($key, $provider, $ttl);
        }
        return $result;
    }
}
