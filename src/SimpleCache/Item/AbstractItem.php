<?php

namespace ryunosuke\SimpleCache\Item;

use Generator;

abstract class AbstractItem
{
    protected const VERSION = '1.0.0';

    private string $filename;
    private array  $metadata;
    private        $value;

    public function __construct(string $filename)
    {
        $this->filename = $filename;
    }

    public function size(): int
    {
        if (!isset($this->metadata['size'])) {
            if ($this->get() === null) {
                $this->metadata['size'] = 0;
            }
            else {
                $this->metadata['size'] = file_exists($this->filename) ? (@filesize($this->filename) ?: 0) : 0;
            }
        }
        return $this->metadata['size'];
    }

    public function get(): mixed
    {
        if (!isset($this->metadata)) {
            $generator      = $this->import($this->filename);
            $this->metadata = $generator->current();
        }

        if (false
            || (($this->metadata['time'] ?? 0) + ($this->metadata['ttl'] ?? 0)) <= time()
            || ($this->metadata['version'] ?? '') !== static::VERSION
            || ($this->metadata['class'] ?? '') !== static::class
        ) {
            return null;
        }

        if (isset($generator) && $generator->valid()) {
            $generator->next();
            $this->value = $generator->current();
        }
        return $this->value;
    }

    public function set(mixed $value, int $ttl): bool
    {
        $this->value = $value;

        $this->metadata['version'] = static::VERSION;
        $this->metadata['class']   = static::class;
        $this->metadata['type']    = gettype($value);
        $this->metadata['time']    = time();
        $this->metadata['ttl']     = $ttl;

        // null is reserved in future scope
        $contents = $this->export($this->filename, $this->metadata, $this->value);
        if ($contents === null) {
            return true; // @codeCoverageIgnore
        }

        $this->metadata['size'] = strlen($contents);

        // scheme context
        $context = null;
        if (preg_match('#^([a-z][-+.0-9a-z]*)://#ui', $this->filename, $matches)) {
            $context = stream_context_create([$matches[1] => $this->metadata]);
        }

        // rename(atomic copy)
        $tmp = $this->filename . uniqid(sprintf('@(%s-%d)', gethostname(), getmypid()), true);
        file_put_contents($tmp, $contents);
        $return = rename($tmp, $this->filename, $context);
        if ($return === false) {
            unlink($tmp);
        }
        return $return;
    }

    public function delete(): bool
    {
        unset($this->value);

        unset($this->metadata);

        return @unlink($this->filename);
    }

    abstract protected function export(string $filename, array $metadata, $value): ?string;

    abstract protected function import(string $filename): Generator;
}
