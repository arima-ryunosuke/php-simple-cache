<?php

namespace ryunosuke\SimpleCache;

use ArrayAccess;
use ryunosuke\SimpleCache\Contract\ArrayAccessTrait;
use ryunosuke\SimpleCache\Contract\CacheInterface;
use ryunosuke\SimpleCache\Contract\CleanableInterface;
use ryunosuke\SimpleCache\Contract\FetchableInterface;
use ryunosuke\SimpleCache\Contract\FetchTrait;
use ryunosuke\SimpleCache\Contract\HashableInterface;
use ryunosuke\SimpleCache\Contract\HashTrait;
use ryunosuke\SimpleCache\Contract\IterableInterface;
use ryunosuke\SimpleCache\Contract\LockableInterface;
use ryunosuke\SimpleCache\Contract\SingleTrait;
use Traversable;

class ChainCache implements CacheInterface, FetchableInterface, HashableInterface, LockableInterface, IterableInterface, CleanableInterface, ArrayAccess
{
    use SingleTrait;
    use FetchTrait;
    use HashTrait;
    use ArrayAccessTrait;

    /** @var CacheInterface[] */
    private array $internals;

    public function __construct($internals)
    {
        $this->internals = $internals;
    }

    // <editor-fold desc="CacheInterface">

    /** @inheritdoc */
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

    /** @inheritdoc */
    public function setMultiple($values, $ttl = null): bool
    {
        $result = true;
        foreach ($this->internals as $internal) {
            $result = $internal->setMultiple($values, $ttl) && $result;
        }
        return $result;
    }

    /** @inheritdoc */
    public function deleteMultiple($keys): bool
    {
        $result = true;
        foreach ($this->internals as $internal) {
            $result = $internal->deleteMultiple($keys) && $result;
        }
        return $result;
    }

    /** @inheritdoc */
    public function clear(): bool
    {
        $result = true;
        foreach ($this->internals as $internal) {
            $result = $internal->clear() && $result;
        }
        return $result;
    }

    /** @inheritdoc */
    public function has($key): bool
    {
        foreach ($this->internals as $internal) {
            if ($internal->has($key)) {
                return true;
            }
        }
        return false;
    }

    // </editor-fold>

    // <editor-fold desc="LockableInterface">

    public function lock($key, int $operation): bool
    {
        /** @var LockableInterface[] $internals */
        $internals = array_filter($this->internals, fn($internal) => $internal instanceof LockableInterface);

        foreach ($internals as $internal) {
            return $internal->lock($key, $operation);
        }
        return false;
    }

    // </editor-fold>

    // <editor-fold desc="IterableInterface">

    /** @inheritdoc */
    public function keys(?string $pattern = null): iterable
    {
        return array_keys($this->items($pattern));
    }

    /** @inheritdoc */
    public function items(?string $pattern = null): iterable
    {
        /** @var IterableInterface[] $internals */
        $internals = array_filter($this->internals, fn($internal) => $internal instanceof IterableInterface);

        $result = [];
        foreach ($internals as $internal) {
            $items  = $internal->items($pattern);
            $result += $items instanceof Traversable ? iterator_to_array($items) : $items;
        }
        return $result;
    }

    // </editor-fold>

    // <editor-fold desc="CleanableInterface">

    /** @inheritdoc */
    public function gc(float $probability, ?float $maxsecond = null): int
    {
        /** @var CleanableInterface[] $internals */
        $internals = array_filter($this->internals, fn($internal) => $internal instanceof CleanableInterface);

        if ($internals && $maxsecond !== null) {
            $maxsecond /= count($internals);
        }

        $result = 0;
        foreach ($internals as $internal) {
            $result += $internal->gc($probability, $maxsecond);
        }
        return $result;
    }

    // </editor-fold>
}
