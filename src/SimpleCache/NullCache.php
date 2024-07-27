<?php

namespace ryunosuke\SimpleCache;

use DateInterval;
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

    private bool $affectedReturnValue;

    public function __construct(bool $affectedReturnValue = true)
    {
        $this->affectedReturnValue = $affectedReturnValue;
    }

    // <editor-fold desc="CacheInterface">

    /** @inheritdoc */
    public function get(string $key, mixed $default = null): mixed
    {
        InvalidArgumentException::normalizeKeyOrThrow($key);

        return $default;
    }

    /** @inheritdoc */
    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        InvalidArgumentException::normalizeKeyOrThrow($key);
        InvalidArgumentException::normalizeTtlOrThrow($ttl);

        return $this->affectedReturnValue;
    }

    /** @inheritdoc */
    public function delete(string $key): bool
    {
        InvalidArgumentException::normalizeKeyOrThrow($key);

        return $this->affectedReturnValue;
    }

    /** @inheritdoc */
    public function clear(): bool
    {
        return $this->affectedReturnValue;
    }

    /** @inheritdoc */
    public function has(string $key): bool
    {
        return false;
    }

    // </editor-fold>

    // <editor-fold desc="LockableInterface">

    public function lock(string $key, int $operation): bool
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
