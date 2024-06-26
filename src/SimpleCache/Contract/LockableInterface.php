<?php

namespace ryunosuke\SimpleCache\Contract;

interface LockableInterface
{
    /**
     * lock item
     *
     * @param string $key key
     * @param int $operation LOCK_**
     * @return bool success
     */
    public function lock(string $key, int $operation): bool;
}
