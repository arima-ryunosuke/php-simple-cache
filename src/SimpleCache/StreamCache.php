<?php

namespace ryunosuke\SimpleCache;

use DateInterval;
use Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ryunosuke\SimpleCache\Contract\AllInterface;
use ryunosuke\SimpleCache\Contract\ArrayAccessTrait;
use ryunosuke\SimpleCache\Contract\FetchTrait;
use ryunosuke\SimpleCache\Contract\HashTrait;
use ryunosuke\SimpleCache\Contract\MultipleTrait;
use ryunosuke\SimpleCache\Exception\InvalidArgumentException;
use ryunosuke\SimpleCache\Item\AbstractItem;
use Throwable;

class StreamCache implements AllInterface
{
    use MultipleTrait;
    use FetchTrait;
    use HashTrait;
    use ArrayAccessTrait;

    private string $directory;
    private string $defaultExtension;
    private bool   $directorySupport;

    private int    $defaultTtl;
    private ?float $lockSecond;
    private int    $memorize;
    private array  $itemClasses;

    /** @var AbstractItem[] */
    private array $items;
    private array $cachemap;
    private array $lockings;

    public function __construct(string $directory, array $options = [])
    {
        assert(strlen($directory));

        // normalize scheme (no use default scheme)
        $directory = strtr($directory, [DIRECTORY_SEPARATOR => '/']);
        if (!preg_match('#^[a-z][-+.0-9a-z]*://#', $directory)) {
            $directory = "file://$directory";
        }

        $this->directory        = $directory;
        $this->defaultExtension = $options['defaultExtension'] ?? 'php';
        $this->directorySupport = $options['directorySupport'] ?? @(function ($directory) {
            try {
                mkdir($directory, 0777, true);
                return is_dir($directory);
            }
            catch (Throwable) {
                return false;
            }
        })($this->directory);

        $this->defaultTtl  = $options['defaultTtl'] ?? 60 * 60 * 24 * 365 * 10;
        $this->lockSecond  = $options['lockSecond'] ?? null;
        $this->memorize    = $options['memorize'] ?? 65535;
        $this->itemClasses = $options['itemClasses'] ?? [];
        $this->itemClasses += [
            'php'           => \ryunosuke\SimpleCache\Item\PhpItem::class,
            'php-serialize' => \ryunosuke\SimpleCache\Item\SerializationItem::class,
        ];

        $this->___defaultTtl = $this->defaultTtl;

        $this->items    = [];
        $this->cachemap = [];
        $this->lockings = [];
    }

    public function __debugInfo(): array
    {
        $classname  = self::class;
        $properties = (array) $this;

        $unsets = [
            "\0$classname\0items",
            "\0$classname\0cachemap",
            "\0$classname\0lockings",
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
        $this->lockings = [];

        return $that;
    }

    // <editor-fold desc="CacheInterface">

    /** @inheritdoc */
    public function get(string $key, mixed $default = null): mixed
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
    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
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
    public function delete(string $key): bool
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
    public function has(string $key): bool
    {
        return $this->get($key, $this) !== $this;
    }

    // </editor-fold>

    // <editor-fold desc="LockableInterface">

    public function lock(string $key, int $operation): bool
    {
        if ($this->lockSecond === null) {
            return false;
        }

        $filename = $this->_filename($key);

        if ($this->lockSecond > 0 && $operation !== LOCK_UN) {
            $operation |= LOCK_NB;
        }

        if (!isset($this->lockings[$filename])) {
            // rename(atomic copy) failed. because flock() uses mandatory locking instead of advisory locking on Windows
            if (DIRECTORY_SEPARATOR === '\\' && strpos($filename, 'file://') === 0) {
                $filename .= '.lock';
            }
            $this->lockings[$filename] = fopen($filename, 'c');
        }
        try {
            $start = microtime(true);
            while ((microtime(true) - $start) < 10) {
                $locked = flock($this->lockings[$filename], $operation);
                if ($locked || $this->lockSecond === 0.0) {
                    return $locked;
                }
                usleep($this->lockSecond * 1000 * 1000);
            }
            return false;
        }
        finally {
            if ($operation === LOCK_UN) {
                fclose($this->lockings[$filename]);
                unset($this->lockings[$filename]);
            }
        }
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
        catch (Exception) {
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
            $key = InvalidArgumentException::normalizeKeyOrThrow($key);

            $keys = explode('.', $key);
            if (isset($this->itemClasses[$keys[count($keys) - 1]])) {
                $ext = array_pop($keys);
            }
            else {
                $ext = $this->defaultExtension;
            }
            $key = implode($this->directorySupport ? "/" : ".", $keys) . ".$ext";

            $filename = "$this->directory/$key";
            $dirname  = dirname($filename);

            if ($this->directorySupport && !is_dir($dirname)) {
                @mkdir($dirname, 0777, true);
            }

            return $filename;
        })($key);
    }

    protected function _key(string $filename): string
    {
        return $this->cachemap[$filename] ??= (function ($filename) {
            $extension = preg_quote($this->getExtension($filename) ?? '', '@');

            $key = preg_replace("@\.$extension($|\?)@u", "", $filename);
            $key = substr($key, strlen($this->directory) + 1);
            $key = strtr($key, ['/' => '.']);

            return $key;
        })($filename);
    }

    private function createItem(string $filename): ?AbstractItem
    {
        $extension = $this->getExtension($filename) ?? '';
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
