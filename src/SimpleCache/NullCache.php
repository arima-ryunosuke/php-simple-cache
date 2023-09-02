<?php

namespace ryunosuke\SimpleCache;

use ryunosuke\SimpleCache\Contract\CacheInterface;
use ryunosuke\SimpleCache\Contract\MultipleTrait;
use ryunosuke\SimpleCache\Exception\InvalidArgumentException;

class NullCache implements CacheInterface
{
    use MultipleTrait;

    private bool $enabledSlashKey;
    private bool $affectedReturnValue;

    public function __construct(bool $enabledSlashKey = false, bool $affectedReturnValue = true)
    {
        $this->enabledSlashKey     = $enabledSlashKey;
        $this->affectedReturnValue = $affectedReturnValue;
    }

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
}
