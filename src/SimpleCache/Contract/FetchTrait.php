<?php

namespace ryunosuke\SimpleCache\Contract;

use DateInterval;
use Traversable;

trait FetchTrait
{
    public function fetch(string $key, callable $provider, null|int|DateInterval $ttl = null)
    {
        $this->_lock($key, LOCK_SH);
        try {
            $value = $this->get($key, $this);
            if ($value === $this) {
                if ($this->_lock($key, LOCK_EX)) {
                    if ($this->has($key)) {
                        // @codeCoverageIgnoreStart edge case. see test case
                        return $this->get($key);
                        // @codeCoverageIgnoreEnd
                    }
                }
                $value = $provider($this);
                $this->set($key, $value, $ttl);
            }
            return $value;
        }
        finally {
            $this->_lock($key, LOCK_UN);
        }
    }

    public function fetchMultiple(iterable $providers, null|int|DateInterval $ttl = null): iterable
    {
        $providers = $providers instanceof Traversable ? iterator_to_array($providers) : $providers;
        $keys      = array_keys($providers);

        foreach ($keys as $key) {
            $this->_lock($key, LOCK_SH);
        }
        try {
            $result   = [];
            $missings = [];
            foreach ($this->getMultiple($keys, $this) as $key => $value) {
                if ($value === $this) {
                    if ($this->_lock($key, LOCK_EX)) {
                        if ($this->has($key)) {
                            // @codeCoverageIgnoreStart edge case. see test case
                            $result[$key] = $this->get($key);
                            continue;
                            // @codeCoverageIgnoreEnd
                        }
                    }
                    $value          = $providers[$key]($this);
                    $missings[$key] = $value;
                }

                $result[$key] = $value;
            }

            $this->setMultiple($missings, $ttl);

            return $result;
        }
        finally {
            foreach ($keys as $key) {
                $this->_lock($key, LOCK_UN);
            }
        }
    }

    private function _lock(string $key, int $operation): bool
    {
        if ($this instanceof LockableInterface) {
            return $this->lock($key, $operation);
        }
        return false;
    }
}
