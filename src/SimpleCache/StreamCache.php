<?php

namespace ryunosuke\SimpleCache;

use Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ryunosuke\SimpleCache\Contract\CacheInterface;
use ryunosuke\SimpleCache\Contract\CleanableInterface;
use ryunosuke\SimpleCache\Contract\FetchableInterface;
use ryunosuke\SimpleCache\Contract\IterableInterface;
use ryunosuke\SimpleCache\Contract\MultipleTrait;
use ryunosuke\SimpleCache\Exception\InvalidArgumentException;
use ryunosuke\SimpleCache\Item\AbstractItem;
use Throwable;

class StreamCache implements CacheInterface, FetchableInterface, IterableInterface, CleanableInterface
{
    use MultipleTrait;

    private string $directory;
    private string $defaultExtension;
    private string $directorySeparator;
    private bool   $directorySupport;

    private int   $defaultTtl;
    private int   $memorize;
    private array $itemClasses;

    /** @var AbstractItem[] */
    private array $items;
    private array $cachemap;

    public function __construct(string $directory, array $options = [])
    {
        assert(strlen($directory));

        // normalize scheme (no use default scheme)
        $directory = strtr($directory, [DIRECTORY_SEPARATOR => '/']);
        if (!preg_match('#^[a-z][-+.0-9a-z]*://#', $directory)) {
            $directory = "file://$directory";
        }

        $this->directory          = $directory;
        $this->defaultExtension   = $options['defaultExtension'] ?? 'php';
        $this->directorySeparator = $options['directorySeparator'] ?? '/';
        $this->directorySupport   = $options['directorySupport'] ?? @(function ($directory) {
            try {
                mkdir($directory, 0777, true);
                return is_dir($directory);
            }
            catch (Throwable $t) {
                return false;
            }
        })($this->directory);

        $this->defaultTtl  = $options['defaultTtl'] ?? 60 * 60 * 24 * 365 * 10;
        $this->memorize    = ($options['memorize'] ?? true) === true ? PHP_INT_MAX : $options['memorize']; // for compatible
        $this->itemClasses = $options['itemClasses'] ?? [];
        $this->itemClasses += [
            'php'           => \ryunosuke\SimpleCache\Item\PhpItem::class,
            'php-serialize' => \ryunosuke\SimpleCache\Item\SerializationItem::class,
        ];

        $this->items    = [];
        $this->cachemap = [];
    }

    public function __debugInfo()
    {
        $classname  = self::class;
        $properties = (array) $this;

        $unsets = [
            "\0$classname\0items",
            "\0$classname\0cachemap",
        ];
        foreach ($unsets as $unset) {
            assert(array_key_exists($unset, $properties));
            unset($properties[$unset]);
        }

        return $properties;
    }

    public function withNamespace(string $namespace): self
    {
        $that = clone $this;

        $that->directory = "$this->directory/$namespace";

        $that->items    = [];
        $this->cachemap = [];

        return $that;
    }

    // <editor-fold desc="CacheInterface">

    /** @inheritdoc */
    public function get($key, $default = null)
    {
        $item = $this->items[$key] ??= $this->createItem($this->_filename($key));

        if ($this->memorize) {
            $this->trimMemo();
        }
        else {
            unset($this->items[$key]);
        }

        return $item->get() ?? $default;
    }

    /** @inheritdoc */
    public function set($key, $value, $ttl = null): bool
    {
        $ttl = InvalidArgumentException::normalizeTtlOrThrow($ttl) ?? $this->defaultTtl;

        if ($ttl <= 0) {
            return $this->delete($key);
        }

        $item = $this->items[$key] ??= $this->createItem($this->_filename($key));

        if ($this->memorize) {
            $this->trimMemo();
        }
        else {
            unset($this->items[$key]);
        }

        return $item->set($value, $ttl);
    }

    /** @inheritdoc */
    public function delete($key): bool
    {
        $item = $this->items[$key] ?? $this->createItem($this->_filename($key));

        unset($this->items[$key]);

        return $item->delete();
    }

    /** @inheritdoc */
    public function clear(): bool
    {
        $this->items = [];

        return $this->deleteMultiple($this->keys());
    }

    /** @inheritdoc */
    public function has($key): bool
    {
        return $this->get($key, $this) !== $this;
    }

    // </editor-fold>

    // <editor-fold desc="FetchableInterface">

    /** @inheritdoc */
    public function fetch($key, $provider, $ttl = null)
    {
        $value = $this->get($key, $this);
        if ($value === $this) {
            $value = $provider($this);
            $this->set($key, $value, $ttl);
        }
        return $value;
    }

    // </editor-fold>

    // <editor-fold desc="IterableInterface">

    /** @inheritdoc */
    public function keys(?string $pattern = null): iterable
    {
        foreach ($this->items($pattern) as $key => $item) {
            yield $key;
        }
    }

    /** @inheritdoc */
    public function items(?string $pattern = null): iterable
    {
        try {
            $rdi = new RecursiveDirectoryIterator($this->directory, RecursiveDirectoryIterator::SKIP_DOTS | RecursiveDirectoryIterator::UNIX_PATHS);
            $rii = new RecursiveIteratorIterator($rdi, RecursiveIteratorIterator::LEAVES_ONLY);

            foreach ($rii as $it) {
                $fullpath = $it->getPathname();
                $key      = $this->_key($fullpath);
                if ($pattern === null || fnmatch($pattern, $key)) {
                    $item = $this->createItem($fullpath);
                    if ($item !== null) {
                        yield $key => $item;
                    }
                }
            }
        }
        catch (Exception $e) {
            // if $this->directory is stream wrapper, not use file_exists/id_dir/other stat function.
            // because that is implementation-dependent, it may even throw an exception.
        }
    }

    // </editor-fold>

    // <editor-fold desc="CleanableInterface">

    /** @inheritdoc */
    public function gc(float $probability, ?float $maxsecond = null): int
    {
        if ($probability < (rand() / getrandmax())) {
            return 0;
        }

        $result = 0;
        $start  = microtime(true);

        // cleanup expired item
        foreach ($this->items() as $key => $item) {
            if ($maxsecond !== null && $maxsecond < (microtime(true) - $start)) {
                return $result; // @codeCoverageIgnore
            }
            if ($item->get() === null) {
                $result++;
                $this->delete($key);
            }
        }

        // cleanup empty directory
        if ($this->directorySupport && is_dir($this->directory)) {
            $rdi = new RecursiveDirectoryIterator($this->directory, RecursiveDirectoryIterator::SKIP_DOTS | RecursiveDirectoryIterator::UNIX_PATHS);
            $rii = new RecursiveIteratorIterator($rdi, RecursiveIteratorIterator::CHILD_FIRST);

            foreach ($rii as $it) {
                if ($maxsecond !== null && $maxsecond < (microtime(true) - $start)) {
                    return $result; // @codeCoverageIgnore
                }
                if ($it->isDir() && !$it->isLink()) {
                    @rmdir($it->getPathname());
                }
            }
        }

        return $result;
    }

    // </editor-fold>

    protected function _filename(string $key): string
    {
        return $this->cachemap[$key] ??= (function ($key) {
            $key = InvalidArgumentException::normalizeKeyOrThrow($key, strpos($this->directorySeparator, '/') !== false);

            $filename = strtr($key, [$this->directorySeparator => '/']);
            if (!$this->directorySupport) {
                $filename = strtr($filename, ['/' => '~']);
            }
            $filename = "$this->directory/$filename";

            $dirname = dirname($filename);
            if ($this->directorySupport && !is_dir($dirname)) {
                @mkdir($dirname, 0777, true);
            }

            $extension = $this->getExtension($filename);
            if (!isset($this->itemClasses[$extension])) {
                $filename .= ".$this->defaultExtension";
            }

            return $filename;
        })($key);
    }

    protected function _key(string $filename): string
    {
        return $this->cachemap[$filename] ??= (function ($filename) {
            $extension = preg_quote($this->getExtension($filename));
            $key       = preg_replace("@\.$extension($|\?)@u", "", $filename);
            $key       = substr($key, strlen($this->directory) + 1);
            $key       = strtr($key, ['/' => $this->directorySeparator]);

            if (!$this->directorySupport) {
                $key = strtr($key, ['~' => '/']);
            }
            return $key;
        })($filename);
    }

    private function createItem(string $filename): ?AbstractItem
    {
        $extension = $this->getExtension($filename);
        $classname = $this->itemClasses[$extension] ?? null;

        if ($classname === null) {
            return null;
        }
        return new $classname($filename);
    }

    private function trimMemo(): void
    {
        // trim by item size in future scope
        if ($this->memorize <= count($this->items)) {
            $this->items = array_slice($this->items, $this->memorize / 3, null, true);
        }
    }

    private static function getExtension(string $url): ?string
    {
        $parts = parse_url($url);
        if ($parts === false) {
            $parts = parse_url(preg_replace('#^[a-z][-+.0-9a-z]*:///#', '', $url));
        }
        if ($parts === false) {
            throw new InvalidArgumentException("$url is seriously malformed URLs");
        }

        // in some schemes, fragment may present a filename (e.g. zip://archive.zip#dir/file.txt)
        if (isset($parts['fragment'])) {
            $pathinfo = pathinfo($parts['fragment']);
            if (isset($pathinfo['extension'])) {
                return $pathinfo['extension'];
            }
        }
        if (isset($parts['path'])) {
            $pathinfo = pathinfo($parts['path']);
            if (isset($pathinfo['extension'])) {
                return $pathinfo['extension'];
            }
        }
        return null;
    }
}
