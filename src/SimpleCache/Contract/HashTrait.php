<?php

namespace ryunosuke\SimpleCache\Contract;

use Closure;
use ryunosuke\SimpleCache\Exception\InvalidArgumentException;
use stdClass;
use Traversable;

trait HashTrait
{
    private Closure $___hashClosure;
    private int     $___defaultTtl = 60 * 60 * 24 * 365 * 10;

    private function ___mapItems(iterable $keys): array
    {
        $hash = $this->___hashClosure ?? static fn($key) => hash('fnv164', $key);

        $hashmap = [];
        foreach ($keys as $key) {
            $hashmap[$hash($key)][] = $key;
        }

        $items = [];
        foreach ($this->getMultiple(array_keys($hashmap), []) as $hashkey => $item) {
            $items[$hashkey] = (object) $item;
        }

        $results = [];
        foreach ($hashmap as $hashkey => $keys) {
            foreach ($keys as $key) {
                $results[$hashkey][$key] = $items[$hashkey];
            }
        }

        return $results;
    }

    private function ___emptyStdClass(stdClass $stdClass): bool
    {
        foreach ($stdClass as $ignored) {
            return false;
        }
        return true;
    }

    public function getByHash($key, $default = null)
    {
        return $this->getMultipleByHash([$key], $default)[$key];
    }

    public function setByHash($key, $value, $ttl = null): bool
    {
        return $this->setMultipleByHash([$key => $value], $ttl);
    }

    public function deleteByHash($key): bool
    {
        return $this->deleteMultipleByHash([$key]);
    }

    public function getMultipleByHash($keys, $default = null): iterable
    {
        $itemsMap = $this->___mapItems($keys);

        $dead   = [];
        $result = [];
        foreach ($itemsMap as $hashkey => $items) {
            foreach ($items as $key => $item) {
                [$value, $expire] = $item->$key ?? [$default, null];
                if ($expire !== null && $expire <= time()) {
                    $value = $default;
                    unset($item->$key);
                }
                $result[$key] = $value;

                if ($this->___emptyStdClass($item)) {
                    $dead[$hashkey] = $item;
                }
            }
        }

        // delete expired and empty item
        if ($dead) {
            $this->deleteMultiple(array_keys($dead));
        }

        return $result;
    }

    public function setMultipleByHash($values, $ttl = null): bool
    {
        $values = $values instanceof Traversable ? iterator_to_array($values) : $values;

        $ttl = InvalidArgumentException::normalizeTtlOrThrow($ttl) ?? $this->___defaultTtl;
        if ($ttl <= 0) {
            return $this->deleteMultipleByHash(array_keys($values));
        }

        $itemsMap = $this->___mapItems(array_keys($values));

        $live = [];
        foreach ($itemsMap as $hashkey => $items) {
            foreach ($items as $key => $item) {
                $item->$key     = [$values[$key], time() + $ttl];
                $live[$hashkey] = $item;
            }
        }

        return $this->setMultiple($live, time() + 365 * 24 * 60 * 60);
    }

    public function deleteMultipleByHash($keys): bool
    {
        $itemsMap = $this->___mapItems($keys);

        $live = $dead = [];
        foreach ($itemsMap as $hashkey => $items) {
            foreach ($items as $key => $item) {
                unset($item->$key);
            }
            foreach ($items as $item) {
                if ($this->___emptyStdClass($item)) {
                    $dead[$hashkey] = $item;
                }
                else {
                    $live[$hashkey] = $item;
                }
            }
        }

        $result = $live || $dead;
        // reset deleted item
        if ($live) {
            $result = $this->setMultiple($live, time() + 365 * 24 * 60 * 60) && $result;
        }
        // delete empty item
        if ($dead) {
            $result = $this->deleteMultiple(array_keys($dead)) && $result;
        }
        return $result;
    }

    public function hasByHash($key): bool
    {
        return $this->getByHash($key, $this) !== $this;
    }
}
