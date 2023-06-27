<?php

namespace ryunosuke\SimpleCache\Contract;

trait MultipleTrait
{
    public function getMultiple($keys, $default = null)
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }
        return $result;
    }

    public function setMultiple($values, $ttl = null)
    {
        $result = true;
        foreach ($values as $key => $value) {
            $result = $this->set($key, $value, $ttl) && $result;
        }
        return $result;
    }

    public function deleteMultiple($keys)
    {
        $result = true;
        foreach ($keys as $key) {
            $result = $this->delete($key) && $result;
        }
        return $result;
    }

    public function fetchMultiple($providers, $ttl = null)
    {
        $result = [];
        foreach ($providers as $key => $provider) {
            $result[$key] = $this->fetch($key, $provider, $ttl);
        }
        return $result;
    }
}
