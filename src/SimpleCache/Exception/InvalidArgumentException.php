<?php

namespace ryunosuke\SimpleCache\Exception;

use DateInterval;
use DateTime;

class InvalidArgumentException extends \InvalidArgumentException implements \Psr\SimpleCache\InvalidArgumentException
{
    public static function normalizeKeyOrThrow(string $key): string
    {
        if ($key === '') {
            throw new static("\$key is empty string");
        }
        if (strpbrk($key, '{}()\\@:/') !== false) {
            throw new static("\$key contains reserved character({}()/\\@:) ($key)");
        }

        return $key;
    }

    public static function normalizeTtlOrThrow(null|int|DateInterval $ttl): ?int
    {
        if ($ttl === null) {
            return null;
        }
        if (is_int($ttl)) {
            return $ttl;
        }
        if ($ttl instanceof DateInterval) {
            return (new DateTime())->setTimestamp(0)->add($ttl)->getTimestamp();
        }
    }
}
