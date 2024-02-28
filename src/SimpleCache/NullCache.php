<?php

namespace ryunosuke\SimpleCache;

use ryunosuke\SimpleCache\Contract\CacheInterface;
use ryunosuke\SimpleCache\Contract\CleanableInterface;
use ryunosuke\SimpleCache\Contract\FetchableInterface;
use ryunosuke\SimpleCache\Contract\FetchTrait;
use ryunosuke\SimpleCache\Contract\IterableInterface;
use ryunosuke\SimpleCache\Contract\MultipleTrait;
use ryunosuke\SimpleCache\Exception\InvalidArgumentException;

class NullCache implements CacheInterface, FetchableInterface, IterableInterface, CleanableInterface
{
    use MultipleTrait;
    use FetchTrait;

    private bool $enabledSlashKey;
    private bool $affectedReturnValue;

    public function __construct(bool $enabledSlashKey = false, bool $affectedReturnValue = true)
    {
        $this->enabledSlashKey     = $enabledSlashKey;
        $this->affectedReturnValue = $affectedReturnValue;
    }

    // <editor-fold desc="CacheInterface">

    /** @inheritdoc */
    public function get($key, $default = null)
    {
        InvalidArgumentException::normalizeKeyOrThrow($key, $this->enabledSlashKey);

        return $default;
    }

    /** @inheritdoc */
    public function set($key, $value, $ttl = null): bool
    {
        InvalidArgumentException::normalizeKeyOrThrow($key, $this->enabledSlashKey);
        InvalidArgumentException::normalizeTtlOrThrow($ttl);

        return $this->affectedReturnValue;
    }

    /** @inheritdoc */
    public function delete($key): bool
    {
        InvalidArgumentException::normalizeKeyOrThrow($key, $this->enabledSlashKey);

        return $this->affectedReturnValue;
    }

    /** @inheritdoc */
    public function clear(): bool
    {
        return $this->affectedReturnValue;
    }

    /** @inheritdoc */
    public function has($key): bool
    {
        return false;
    }

    // </editor-fold>

    // <editor-fold desc="IterableInterface">

    /** @inheritdoc */
    public function keys(?string $pattern = null): iterable
    {
        return [];
    }

    /** @inheritdoc */
    public function items(?string $pattern = null): iterable
    {
        return [];
    }

    // </editor-fold>

    // <editor-fold desc="CleanableInterface">

    /** @inheritdoc */
    public function gc(float $probability, ?float $maxsecond = null): int
    {
        return 0;
    }

    // </editor-fold>
}
