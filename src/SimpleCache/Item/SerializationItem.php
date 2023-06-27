<?php

namespace ryunosuke\SimpleCache\Item;

use Generator;

class SerializationItem extends AbstractItem
{
    protected function export(string $filename, array $metadata, $value): ?string
    {
        return serialize($metadata) . "\n" . serialize($value);
    }

    protected function import(string $filename): Generator
    {
        $fp = @fopen($filename, 'r');
        if ($fp !== false) {
            try {
                yield @unserialize(fgets($fp)) ?: [];
                yield @unserialize(stream_get_contents($fp)) ?: null;
            }
            finally {
                fclose($fp);
            }
        }
        else {
            yield from [[], null];
        }
    }
}
