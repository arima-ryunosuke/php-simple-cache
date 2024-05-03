<?php

namespace ryunosuke\SimpleCache\Contract;

use DateInterval;

trait SingleTrait
{
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->getMultiple([$key], $default)[$key];
    }

    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        return $this->setMultiple([$key => $value], $ttl);
    }

    public function delete(string $key): bool
    {
        return $this->deleteMultiple([$key]);
    }
}
