<?php

namespace ryunosuke\SimpleCache\Item;

use ErrorException;
use Generator;
use ryunosuke\SimpleCache\Exception\CacheException;
use ryunosuke\SimpleCache\Utils;
use Symfony\Component\VarExporter\Exception\NotInstantiableTypeException;
use Symfony\Component\VarExporter\VarExporter;
use Throwable;

class PhpItem extends AbstractItem
{
    private string $fallbackname;

    public function __construct(string $filename)
    {
        parent::__construct($filename);

        $fallbackdir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ryunosuke-simple-cache';
        if (!is_dir($fallbackdir)) {
            @mkdir($fallbackdir); // @codeCoverageIgnore
        }

        $this->fallbackname = $fallbackdir . DIRECTORY_SEPARATOR . rawurlencode($filename);
    }

    public function set($value, int $ttl): bool
    {
        @unlink($this->fallbackname);

        return parent::set($value, $ttl);
    }

    public function delete(): bool
    {
        @unlink($this->fallbackname);

        return parent::delete();
    }

    protected function export(string $filename, array $metadata, $value): ?string
    {
        try {
            $string = VarExporter::export($value);
        }
        catch (NotInstantiableTypeException $e) {
            $string = Utils::var_export3($value, true);
        }

        return "<?php return (function () {
            yield " . var_export($metadata, true) . ";
            yield $string;
        })();";
    }

    protected function import(string $filename): Generator
    {
        set_error_handler(static function ($errno, $errstr, $errfile, $errline) {
            throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
        });

        try {
            if (stream_is_local($filename)) {
                yield from include $filename;
            }
            else {
                if (!file_exists($this->fallbackname)) {
                    //copy($filename, $this->fallbackname);
                    @file_put_contents($this->fallbackname, file_get_contents($filename), LOCK_EX);
                }
                yield from include $this->fallbackname;
            }
        }
        catch (ErrorException $e) {
            // race condition: e.g. S3 errors if file does not exists
            yield from [[], null];
        }
        catch (Throwable $t) {
            throw new CacheException('unknown error', 0, $t);
        }
        finally {
            restore_error_handler();
        }
    }
}
