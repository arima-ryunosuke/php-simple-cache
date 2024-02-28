<?php

namespace ryunosuke\SimpleCache\Contract;

use Traversable;

trait FetchTrait
{
    public function fetch($key, $provider, $ttl = null)
    {
        $value = $this->get($key, $this);
        if ($value === $this) {
            $value = $provider($this);
            $this->set($key, $value, $ttl);
        }
        return $value;
    }

    public function fetchMultiple(iterable $providers, $ttl = null): iterable
    {
        $providers = $providers instanceof Traversable ? iterator_to_array($providers) : $providers;

        $result   = [];
        $missings = [];
        foreach ($this->getMultiple(array_keys($providers), $this) as $key => $value) {
            if ($value === $this) {
                $value          = $providers[$key]($this);
                $missings[$key] = $value;
            }

            $result[$key] = $value;
        }

        $this->setMultiple($missings);

        return $result;
    }
}
