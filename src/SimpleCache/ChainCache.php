<?php

namespace ryunosuke\SimpleCache;

use Psr\SimpleCache\CacheInterface;
use ryunosuke\SimpleCache\Contract\MultipleTrait;

class ChainCache implements CacheInterface
{
    use MultipleTrait;

    /** @var CacheInterface[] */
    private array $internals;

    public function __construct($internals)
    {
        $this->internals = $internals;
    }

    public function get($key, $default = null)
    {
        $result  = $this;
        $setters = [];

        // get which
        foreach ($this->internals as $internal) {
            $result = $internal->get($key, $this);
            if ($result !== $this) {
                break;
            }
            $setters[] = $internal;
        }

        // not found
        if ($result === $this) {
            return $default;
        }

        // sync other
        foreach ($setters as $setter) {
            $setter->set($key, $result);
        }

        return $result;
    }

    public function set($key, $value, $ttl = null)
    {
        $result = true;
        foreach ($this->internals as $internal) {
            $result = $internal->set($key, $value, $ttl) && $result;
        }
        return $result;
    }

    public function delete($key)
    {
        $result = true;
        foreach ($this->internals as $internal) {
            $result = $internal->delete($key) && $result;
        }
        return $result;
    }

    public function clear()
    {
        $result = true;
        foreach ($this->internals as $internal) {
            $result = $internal->clear() && $result;
        }
        return $result;
    }

    public function has($key)
    {
        foreach ($this->internals as $internal) {
            if ($internal->has($key)) {
                return true;
            }
        }
        return false;
    }
}
