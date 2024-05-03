<?php

namespace ryunosuke\SimpleCache\Contract;

use ArrayAccess;
use Psr\SimpleCache\CacheInterface;

interface AllInterface extends
    CacheInterface,
    ArrayAccess,
    CleanableInterface,
    FetchableInterface,
    HashableInterface,
    IterableInterface,
    LockableInterface
{
}
