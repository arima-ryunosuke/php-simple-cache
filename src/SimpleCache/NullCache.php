<?php

namespace ryunosuke\SimpleCache;

use ryunosuke\SimpleCache\Contract\AllInterface;
use ryunosuke\SimpleCache\Contract\ArrayAccessTrait;
use ryunosuke\SimpleCache\Contract\FetchTrait;
use ryunosuke\SimpleCache\Contract\HashTrait;
use ryunosuke\SimpleCache\Contract\MultipleTrait;
use ryunosuke\SimpleCache\Exception\InvalidArgumentException;

class NullCache implements AllInterface
{
    use MultipleTrait;
    use FetchTrait;
    use HashTrait;
    use ArrayAccessTrait;

    private bool $enabledSlashKey;
    private bool $affectedReturnValue;

    public function __construct(bool $enabledSlashKey = false, bool $affectedReturnValue = true)
    {
        $this->enabledSlashKey     = $enabledSlashKey;
        $this->affectedReturnValue = $affectedReturnValue;
    }

    // <editor-fold desc="CacheInterface">

    /** @inheritdoc */
    public function get($key, $default = null): mixed
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

    // <editor-fold desc="LockableInterface">

    public function lock($key, int $operation): bool
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
