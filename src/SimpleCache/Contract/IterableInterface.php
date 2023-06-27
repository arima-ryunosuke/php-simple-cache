<?php

namespace ryunosuke\SimpleCache\Contract;

use ryunosuke\SimpleCache\Item\AbstractItem;

interface IterableInterface
{
    /**
     * get list of keys
     *
     * @param string|null $pattern filter pattern
     * @return iterable|string[] iterable of keys
     */
    public function keys(?string $pattern = null): iterable;

    /**
     * get assoc of items
     *
     * @param string|null $pattern filter pattern
     * @return iterable|AbstractItem[] key => AbstractItem
     */
    public function items(?string $pattern = null): iterable;
}
