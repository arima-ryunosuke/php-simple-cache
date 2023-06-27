<?php

namespace ryunosuke\SimpleCache\Contract;

interface CleanableInterface
{
    /**
     * delete invalid/expired items
     *
     * @param float $probability triggering probability
     * @param float|null $maxsecond limit time
     * @return int deleted count
     */
    public function gc(float $probability, ?float $maxsecond = null): int;
}
