<?php

namespace ryunosuke\SimpleCache;

use ryunosuke\SimpleCache\Contract\CacheInterface;
use ryunosuke\SimpleCache\Contract\SingleTrait;
use Traversable;

class ChainCache implements CacheInterface
{
    use SingleTrait;

    /** @var CacheInterface[] */
    private array $internals;

    public function __construct($internals)
    {
        $this->internals = $internals;
    }

    public function getMultiple($keys, $default = null): iterable
    {
        $keys   = $keys instanceof Traversable ? iterator_to_array($keys) : $keys;
        $keymap = array_flip($keys);

        $result   = [];
        $missings = [];

        // get from internals
        foreach ($this->internals as $n => $internal) {
            $values = $internal->getMultiple($keys, $this);

            foreach ($values as $key => $value) {
                // missing
                if ($value === $this) {
                    $missings[$n][$key] = true;
                }
                // found
                else {
                    $result[$key] = $value;
                    unset($keys[$keymap[$key]]);
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

        return $result + array_fill_keys($keys, $default);
    }

    public function setMultiple($values, $ttl = null): bool
    {
        $result = true;
        foreach ($this->internals as $internal) {
            $result = $internal->setMultiple($values, $ttl) && $result;
        }
        return $result;
    }

    public function deleteMultiple($keys): bool
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

    public function clear(): bool
    {
        $result = true;
        foreach ($this->internals as $internal) {
            $result = $internal->clear() && $result;
        }
        return $result;
    }

    public function has($key): bool
    {
        foreach ($this->internals as $internal) {
            if ($internal->has($key)) {
                return true;
            }
        }
        return false;
    }
}
