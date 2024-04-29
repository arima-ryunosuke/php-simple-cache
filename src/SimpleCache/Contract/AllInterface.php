<?php

namespace ryunosuke\SimpleCache\Contract;

use ArrayAccess;

interface AllInterface extends
    ArrayAccess,
    CacheInterface,
    CleanableInterface,
    FetchableInterface,
    HashableInterface,
    IterableInterface,
    LockableInterface
{
}
