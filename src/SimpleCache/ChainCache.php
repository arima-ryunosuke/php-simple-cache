<?php

namespace ryunosuke\SimpleCache;

use Psr\SimpleCache\CacheInterface;
use ryunosuke\SimpleCache\Contract\SingleTrait;

class ChainCache implements CacheInterface
{
    use SingleTrait;

    /** @var CacheInterface[] */
    private array $internals;

    public function __construct($internals)
    {
        $this->internals = $internals;
    }

    public function getMultiple($keys, $default = null)
    {
        $result   = [];
        $defaults = [];
        $missings = [];

        // get from internals
        foreach ($this->internals as $n => $internal) {
            $values = $internal->getMultiple($keys, $this);

            $keys = [];
            foreach ($values as $key => $item) {
                // missing
                if ($item === $this) {
                    $keys[]             = $key;
                    $defaults[]         = $key;
                    $missings[$n][$key] = true;
                }
                // found
                else {
                    $result[$key] = $item;
                }
            }

            // found all
            if (!$keys) {
                break;
            }
        }

        // sync other
        foreach ($missings as $n => $missingKeys) {
            $this->internals[$n]->setMultiple(array_intersect_key($result, $missingKeys));
        }

        return $result + array_fill_keys($defaults, $default);
    }

    public function setMultiple($values, $ttl = null)
    {
        $result = true;
        foreach ($this->internals as $internal) {
            $result = $internal->setMultiple($values, $ttl) && $result;
        }
        return $result;
    }

    public function deleteMultiple($keys)
    {
        $result = true;
        foreach ($this->internals as $internal) {
            $result = $internal->deleteMultiple($keys) && $result;
        }
        return $result;
    }

    public function fetchMultiple($providers, $ttl = null)
    {
        $result = true;
        foreach ($this->internals as $internal) {
            $result = $internal->fetchMultiple($providers, $ttl) && $result;
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
