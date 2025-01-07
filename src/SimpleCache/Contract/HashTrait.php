<?php

namespace ryunosuke\SimpleCache\Contract;

use Closure;
use DateInterval;
use Traversable;

trait HashTrait
{
    private Closure $___hashClosure;

    private function ___mapHash(iterable $keys): array
    {
        $this->___hashClosure ??= static function ($key) {
            return rtrim(strtr(base64_encode(implode("\n", [
                hash('sha256', $key, true),
                hash('fnv164', $key, true),
            ])), ['/' => '_']), '=');
        };

        $result = [];
        foreach ($keys as $key) {
            $hashedKey = ($this->___hashClosure)($key);
            if (isset($result[$hashedKey])) {
                trigger_error("hash collision($key vs {$result[$hashedKey]})", E_USER_WARNING);
            }
            $result[$hashedKey] = $key;
        }
        return $result;
    }

    public function getByHash(string $key, mixed $default = null)
    {
        return $this->getMultipleByHash([$key], $default)[$key];
    }

    public function setByHash(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        return $this->setMultipleByHash([$key => $value], $ttl);
    }

    public function deleteByHash(string $key): bool
    {
        return $this->deleteMultipleByHash([$key]);
    }

    public function fetchByHash(string $key, callable $provider, null|int|DateInterval $ttl = null)
    {
        return $this->fetchMultipleByHash([$key => $provider], $ttl)[$key];
    }

    public function getMultipleByHash(iterable $keys, mixed $default = null): iterable
    {
        $keys = $keys instanceof Traversable ? iterator_to_array($keys) : $keys;

        $hashedKeys = $this->___mapHash($keys);
        $values     = array_fill_keys($keys, $default);
        foreach ($this->getMultiple(array_keys($hashedKeys), []) as $hashedKey => $value) {
            $key = $hashedKeys[$hashedKey];
            if (isset($value[1]) && $value[1] !== $key) {
                trigger_error("hash collision($key vs $value[1])", E_USER_WARNING);
                $value[0] = null;
            }
            $values[$key] = $value[0] ?? $default;
        }
        return $values;
    }

    public function setMultipleByHash(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        $values = $values instanceof Traversable ? iterator_to_array($values) : $values;

        $hashedKeys = $this->___mapHash(array_keys($values));
        $values2    = array_map(fn($key) => [$values[$key], $key], $hashedKeys);
        return $this->setMultiple($values2, $ttl);
    }

    public function deleteMultipleByHash(iterable $keys): bool
    {
        $hashedKeys = $this->___mapHash($keys);
        return $this->deleteMultiple(array_keys($hashedKeys));
    }

    public function fetchMultipleByHash(iterable $providers, null|int|DateInterval $ttl = null): iterable
    {
        $providers = $providers instanceof Traversable ? iterator_to_array($providers) : $providers;

        $hashedKeys = $this->___mapHash(array_keys($providers));
        $providers2 = array_map(fn($key) => fn() => [$providers[$key](), $key], $hashedKeys);
        $values     = [];
        foreach ($this->fetchMultiple($providers2, $ttl) as $hashedKey => $value) {
            $key = $hashedKeys[$hashedKey];
            if (isset($value[1]) && $value[1] !== $key) {
                trigger_error("hash collision($key vs $value[1])", E_USER_WARNING);
                $value[0] = $providers[$key]();
            }
            $values[$key] = $value[0];
        }
        return $values;
    }

    public function hasByHash(string $key): bool
    {
        return $this->getByHash($key, $this) !== $this;
    }
}
