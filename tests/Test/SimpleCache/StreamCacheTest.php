<?php

namespace ryunosuke\Test\SimpleCache;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ryunosuke\SimpleCache\Item\AbstractItem;
use ryunosuke\SimpleCache\StreamCache;
use ryunosuke\StreamWrapper\Stream\MysqlStream;
use ryunosuke\StreamWrapper\Stream\RedisStream;
use ryunosuke\StreamWrapper\Stream\S3Stream;
use ryunosuke\Test\AbstractTestCase;

class StreamCacheTest extends AbstractTestCase
{
    public static function provideUrl()
    {
        $dataset = [];

        @mkdir($cachedir = sys_get_temp_dir() . '/stream-cache', 0777, true);
        $rdi = new RecursiveDirectoryIterator($cachedir, RecursiveDirectoryIterator::SKIP_DOTS);
        $rii = new RecursiveIteratorIterator($rdi, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($rii as $entry) {
            if ($entry->isLink() || $entry->isFile()) {
                unlink($entry);
            }
            else {
                rmdir($entry);
            }
        }
        $dataset['file'] = [$cachedir, ['lockSecond' => 0.1], null];

        if (REDIS_URL) {
            $dataset['redis'] = [REDIS_URL, ['lockSecond' => null], RedisStream::class];
        }
        if (MYSQL_URL) {
            $dataset['mysql'] = [MYSQL_URL, ['lockSecond' => 0], MysqlStream::class];
        }
        if (S3_URL) {
            $dataset['s3'] = [S3_URL, ['directorySupport' => true, 'lockSecond' => null], S3Stream::class];
        }
        return $dataset;
    }

    /**
     * @dataProvider provideUrl
     */
    function test___debugInfo($url, $options)
    {
        $this->expectOutputRegex('#:private#');
        $cache = new StreamCache($url, $options);
        var_dump($cache);
    }

    /**
     * @dataProvider provideUrl
     */
    function test_ArrayAccess($url, $options)
    {
        $cache = new StreamCache($url, $options);

        $time = microtime(true);

        $cache[$this->id] = 1;
        $cache[$this->id] ??= sleep(10); // not call sleep

        that(microtime(true) - $time)->lte(1);

        that(isset($cache[$this->id]))->is(true);
        that($cache[$this->id])->is(1);
        unset($cache[$this->id]);
        that(isset($cache[$this->id]))->is(false);
    }

    /**
     * @dataProvider provideUrl
     */
    function test_withNamespace($url, $options)
    {
        $cache    = new StreamCache($url, $options);
        $subcache = $cache->withNamespace('subcache', [
            'defaultTtl' => 10,
        ]);

        that($subcache)->defaultTtl->is(10);

        $subcache->clear();
        $subcache->set($this->id, 'subitem');

        that($subcache)->get($this->id)->is('subitem');
        if (that($cache)->directorySupport->return()) {
            that($cache)->keys()->contains("subcache.$this->id");
        }
    }

    /**
     * @dataProvider provideUrl
     */
    function test_lock($url, $options, $stream_class)
    {
        /*
         * # nonlock(total 3~6 seconds)
         * | client1                   | client2                   |
         * |---------------------------|---------------------------|
         * | fetch                     |                           |
         * |   get                     |                           |
         * |   provide                 | fetch                     |
         * |   ...step 1               |   get                     |
         * |   ...step 2               |   provide                 |
         * |   ...step 3               |   ...step 1               |
         * |   set                     |   ...step 2               |
         * | return                    |   ...step 3               |
         * |                           |   set                     |
         * |                           | return                    |
         *
         * # uselock(total 3 seconds)
         * | client1                   | client2                   |
         * |---------------------------|---------------------------|
         * | fetch                     |                           |
         * |   get(LOCK_SH)            |                           |
         * |   provide(LOCK_EX)        |                           |
         * |   ...step 1               | fetch                     |
         * |   ...step 2               |   get(LOCK_SH)            |
         * |   ...step 3               |   ...wait client1 LOCK_EX |
         * |   set(LOCK_UN)            |   release lock            |
         * | return                    | return                    |
         *
         * # deadlock
         * | client1                   | client2                   |
         * |---------------------------|---------------------------|
         * | fetch                     | fetch                     |
         * |   get(LOCK_SH)            |   get(LOCK_SH)            |
         * |   provide(LOCK_EX)        |   provide(LOCK_EX)        |
         * |   ...wait client2 LOCK_SH |   ...wait client1 LOCK_SH |
         *
         * # edge case(race condition)
         * | client1                   | client2                   |
         * |---------------------------|---------------------------|
         * | fetch                     |                           |
         * |   get(LOCK_SH)            |                           |
         * |                           | fetch                     |
         * |                           |   get(LOCK_SH)            |
         * |                           |   provide(LOCK_EX)        |
         * |                           |   ...wait 1               |
         * |                           |   ...wait 2               |
         * |                           |   ...wait 3               |
         * |                           |   release lock            |
         * |                           | return                    |
         * |   provide(LOCK_EX)        |                           |
         * |   has                     |                           |
         * |   return                  |                           |
         */

        if ($options['lockSecond'] === null) {
            $this->markTestSkipped();
        }

        $cache = new StreamCache($url, $options);

        $id = $this->id;

        $this->backgroundTask(function () use ($url, $options, $stream_class, $id) {
            if ($stream_class) {
                $stream_class::register($url);
            }
            $cache = new StreamCache($url, $options);
            return $cache->fetch($id, function () {
                sleep(5);
                return 1;
            });
        });
        sleep(3);

        $time = microtime(true);
        that($cache)->fetch($id, fn() => 'dummy')->is(1);
        that(microtime(true) - $time)->isBetween(2.0, 4.5);

        $this->backgroundTask(function () use ($url, $options, $stream_class, $id) {
            if ($stream_class) {
                $stream_class::register($url);
            }
            $cache = new StreamCache($url, $options);
            return $cache->fetchMultiple([
                "$id-1" => function () {
                    sleep(5);
                    return 1;
                },
                "$id-2" => function () {
                    sleep(5);
                    return 2;
                },
            ]);
        });
        sleep(3);

        $time = microtime(true);
        that($cache)->fetchMultiple([
            "$id-1" => fn() => 'dummy',
            "$id-2" => fn() => 'dummy',
        ])->is([
            "$id-1" => 1,
            "$id-2" => 2,
        ]);
        that(microtime(true) - $time)->isBetween(7.0, 9.5);

        if ($options['lockSecond'] > 0) {
            $this->backgroundTask(function () use ($url, $options, $stream_class, $id) {
                if ($stream_class) {
                    $stream_class::register($url);
                }
                $cache = new StreamCache($url, $options);
                return $cache->fetch("$id-10", function () {
                    sleep(15);
                    return 1;
                });
            });
            sleep(3);

            $time = microtime(true);
            that($cache)->fetch("$id-10", fn() => 'dummy')->is('dummy');
            that(microtime(true) - $time)->isBetween(12.0, 14.5);
        }
    }

    /**
     * @dataProvider provideUrl
     */
    function test_memorize($url, $options)
    {
        $cache = new StreamCache($url, $options + ['memorize' => null]);

        that($cache)->set($this->id, 'abc')->isTrue();
        that($cache)->get($this->id)->is('abc');
        that($cache)->items->hasKey($this->id);
        that($cache)->delete($this->id)->isTrue();
        that($cache)->items->notHasKey($this->id);

        $cache = new StreamCache($url, $options + ['memorize' => 0]);

        that($cache)->set($this->id, 'abc')->isTrue();
        that($cache)->get($this->id)->is('abc');
        that($cache)->items->notHasKey($this->id);
        that($cache)->delete($this->id)->isTrue();
        that($cache)->items->notHasKey($this->id);

        $cache = new StreamCache($url, $options + ['memorize' => 9]);

        that($cache)->set("$this->id-1", '1')->isTrue();
        that($cache)->set("$this->id-2", '2')->isTrue();
        that($cache)->set("$this->id-3", '3')->isTrue();
        that($cache)->set("$this->id-4", '4')->isTrue();
        that($cache)->set("$this->id-5", '5')->isTrue();
        that($cache)->set("$this->id-6", '6')->isTrue();
        that($cache)->set("$this->id-7", '7')->isTrue();
        that($cache)->set("$this->id-8", '8')->isTrue();
        that($cache)->set("$this->id-9", '9')->isTrue();
        that($cache)->items->count(6);
        that($cache)->items->hasKey("$this->id-4");
        that($cache)->items->notHasKey("$this->id-3");
        that($cache)->items->notHasKey("$this->id-2");
        that($cache)->items->notHasKey("$this->id-1");
    }

    /**
     * @dataProvider provideUrl
     */
    function test_set_get_has_delete($url, $options)
    {
        $cache = new StreamCache($url, $options);

        that($cache)->set($this->id, 'abc')->isTrue();
        that($cache)->get($this->id)->is('abc');
        that($cache)->has($this->id)->isTrue();
        that($cache)->delete($this->id)->isTrue();
        that($cache)->has($this->id)->isFalse();
    }

    /**
     * @dataProvider provideUrl
     */
    function test_other_instance($url, $options)
    {
        $cache1 = new StreamCache($url, $options);
        $cache2 = new StreamCache($url, $options);

        clearstatcache();
        that($cache1)->set($this->id, 'xyz')->isTrue();
        clearstatcache();
        that($cache2)->get($this->id, 'xyz')->is('xyz');
    }

    /**
     * @dataProvider provideUrl
     */
    function test_other_delete($url, $options)
    {
        $cache = new StreamCache($url, $options + ['memorize' => false]);

        $cache->set($this->id, 'data');

        /** @noinspection PhpParamsInspection */
        $item = iterator_to_array($cache->items($this->id))[$this->id];

        that($cache)->has($this->id)->isTrue();
        that($item)->delete()->isTrue();
        that($item)->get()->isNull();
        that($cache)->get($this->id)->isNull();
    }

    /**
     * @dataProvider provideUrl
     */
    function test_set_get_has_delete_multiple($url, $options)
    {
        $cache = new StreamCache($url, $options);

        that($cache)->setMultiple([
            "$this->id.x" => 'x',
            "$this->id.y" => 'y',
            "$this->id.z" => 'z',
        ])->isTrue();

        that($cache)->getMultiple([
            "$this->id.x",
            "$this->id.z",
        ])->is([
            "$this->id.x" => 'x',
            "$this->id.z" => 'z',
        ]);

        that($cache)->deleteMultiple([
            "$this->id.x",
            "$this->id.y",
        ])->isTrue();

        that($cache)->has("$this->id.x")->isFalse();
        that($cache)->has("$this->id.y")->isFalse();
        that($cache)->has("$this->id.z")->isTrue();

        $called   = [];
        $provider = function ($value) use (&$called) {
            $called[] = $value;
            return $value;
        };
        that($cache)->fetchMultiple([
            "$this->id.x" => fn() => $provider('X'),
            "$this->id.y" => fn() => $provider('Y'),
            "$this->id.z" => fn() => $provider('Z'),
        ])->is([
            "$this->id.x" => 'X',
            "$this->id.y" => 'Y',
            "$this->id.z" => 'z',
        ]);
        that($called)->is(['X', 'Y']);
    }

    /**
     * @dataProvider provideUrl
     */
    function test_fetch($url, $options)
    {
        $cache = new StreamCache($url, $options);

        $cache->deleteMultiple([
            "$this->id.x",
            "$this->id.x.y",
            "$this->id.x.y.z",
        ]);

        that($cache)->fetch("$this->id.x", fn() => 'aX')->is('aX');
        that($cache)->fetch("$this->id.x.y", fn() => 'bX')->is('bX');
        that($cache)->fetch("$this->id.x.y.z", fn() => 'cX')->is('cX');
    }

    /**
     * @dataProvider provideUrl
     */
    function test_hash($url, $options)
    {
        $cache = new StreamCache($url, $options);

        $invalidPrefix = '{}()\\@:';

        that($cache)->hasByHash("$invalidPrefix.$this->id/hoge")->isFalse();
        that($cache)->setByHash("$invalidPrefix.$this->id/hoge", 'Hoge')->isTrue();
        that($cache)->fetchByHash("$invalidPrefix.$this->id/hoge", fn() => 'Hoge2')->is('Hoge');
        that($cache)->getByHash("$invalidPrefix.$this->id/hoge")->is('Hoge');
        that($cache)->setByHash("$invalidPrefix.$this->id/fuga", 'Fuga', 0)->isFalse();
        that($cache)->deleteByHash("$invalidPrefix.$this->id/hoge")->isTrue();
        that($cache)->hasByHash("$invalidPrefix.$this->id/hoge")->isFalse();
        that($cache)->fetchByHash("$invalidPrefix.$this->id/hoge", fn() => 'Hoge2')->is('Hoge2');

        that($cache)->setMultipleByHash([
            "$invalidPrefix.$this->id/x" => 'X',
            "$invalidPrefix.$this->id/y" => 'Y',
            "$invalidPrefix.$this->id/z" => 'Z',
        ])->isTrue();
        that($cache)->getMultipleByHash([
            "$invalidPrefix.$this->id/x",
            "$invalidPrefix.$this->id/y",
            "$invalidPrefix.$this->id/z",
        ])->is([
            "$invalidPrefix.$this->id/x" => 'X',
            "$invalidPrefix.$this->id/y" => 'Y',
            "$invalidPrefix.$this->id/z" => 'Z',
        ]);
        that($cache)->deleteMultipleByHash([
            "$invalidPrefix.$this->id/x",
        ])->isTrue();

        that($cache)->fetchMultipleByHash([
            "$invalidPrefix.$this->id/x" => fn() => 'X1',
            "$invalidPrefix.$this->id/y" => fn() => 'Y1',
            "$invalidPrefix.$this->id/z" => fn() => 'Z1',
        ], 1)->is([
            "$invalidPrefix.$this->id/x" => 'X1',
            "$invalidPrefix.$this->id/y" => 'Y',
            "$invalidPrefix.$this->id/z" => 'Z',
        ]);
        that($cache)->getMultipleByHash([
            "$invalidPrefix.$this->id/x",
            "$invalidPrefix.$this->id/y",
            "$invalidPrefix.$this->id/z",
        ])->is([
            "$invalidPrefix.$this->id/x" => 'X1',
            "$invalidPrefix.$this->id/y" => 'Y',
            "$invalidPrefix.$this->id/z" => 'Z',
        ]);

        sleep(2);
        that($cache)->getMultipleByHash([
            "$invalidPrefix.$this->id/x",
            "$invalidPrefix.$this->id/y",
            "$invalidPrefix.$this->id/z",
        ], 'nothing')->is([
            "$invalidPrefix.$this->id/x" => 'nothing',
            "$invalidPrefix.$this->id/y" => 'Y',
            "$invalidPrefix.$this->id/z" => 'Z',
        ]);

        that($cache)->deleteMultipleByHash([
            "$invalidPrefix.$this->id/none",
        ])->isFalse();
        that($cache)->deleteMultipleByHash([
            "$invalidPrefix.$this->id/y",
        ])->isTrue();
        that($cache)->getByHash("$invalidPrefix.$this->id/y")->is(null);
        that($cache)->getByHash("$invalidPrefix.$this->id/z")->is('Z');
        that($cache)->deleteMultipleByHash([
            "$invalidPrefix.$this->id/z",
        ])->isTrue();
        that($cache)->getByHash("$invalidPrefix.$this->id/y")->is(null);
        that($cache)->getByHash("$invalidPrefix.$this->id/z")->is(null);

        that($cache)->___hashClosure = fn() => 'hashed';

        $error = null;
        set_error_handler(function ($level, $message) use (&$error) {
            if (!(error_reporting() & $level)) {
                return false;
            }
            $error = [
                'level'   => $level,
                'message' => $message,
            ];
        });

        try {
            $cache->setMultipleByHash([
                "$this->id/a" => 'A1',
                "$this->id/b" => 'B1',
            ]);
            that($error)->is([
                'level'   => E_USER_WARNING,
                'message' => "hash collision($this->id/b vs $this->id/a)",
            ]);

            $cache->setByHash("$this->id/a", 'A1');
            $cache->setByHash("$this->id/b", 'B1');
            that($cache)->getByHash("$this->id/a")->is(null);
            that($cache)->getByHash("$this->id/b")->is('B1');
            that($error)->is([
                'level'   => E_USER_WARNING,
                'message' => "hash collision($this->id/a vs $this->id/b)",
            ]);

            that($cache)->fetchByHash("$this->id/a", fn() => 'A2')->is('A2');
            that($cache)->fetchByHash("$this->id/b", fn() => 'B2')->is('B1');
            that($error)->is([
                'level'   => E_USER_WARNING,
                'message' => "hash collision($this->id/a vs $this->id/b)",
            ]);
        }
        finally {
            restore_error_handler();
        }
    }

    /**
     * @dataProvider provideUrl
     */
    function test_clear($url, $options)
    {
        $cache = new StreamCache($url, $options);

        $cache->set($this->id, 'X');
        $cache->set("$this->id.a", 'a');
        $cache->set("$this->id.a.b", 'b');
        $cache->set("$this->id.a.b.c", 'c');

        that($cache)->clear()->isTrue();
        that($cache)->has($this->id)->isFalse();
        that($cache)->has("$this->id.a")->isFalse();
        that($cache)->has("$this->id.a.b")->isFalse();
        that($cache)->has("$this->id.a.b.c")->isFalse();
    }

    /**
     * @dataProvider provideUrl
     */
    function test_expire($url, $options)
    {
        $cache = new StreamCache($url, $options);

        that($cache)->set($this->id, 'abc', 2)->isTrue();
        that($cache)->get($this->id)->is('abc');
        sleep(2);
        that($cache)->has($this->id)->isFalse();

        $cache->set($this->id, 'abc', 0);
        that($cache)->has($this->id)->isFalse();
    }

    /**
     * @dataProvider provideUrl
     */
    function test_iterable($url, $options)
    {
        $cache = new StreamCache($url, $options);

        $cache->set("$this->id.a", 'a');
        $cache->set("$this->id.a.b", 'b');
        $cache->set("$this->id.a.b.c", 'c');

        that($cache)->keys("$this->id.a.b*")->is(["$this->id.a.b", "$this->id.a.b.c"], null, true);
        that($cache)->items("$this->id.a.b*")->eachIsInstanceOf(AbstractItem::class);
    }

    /**
     * @dataProvider provideUrl
     */
    function test_gc($url, $options)
    {
        $cache = new StreamCache($url, ['gcArgs' => 1.0] + $options);

        $cache->clear();
        $cache->set("$this->id.a", 'a', 1);
        $cache->set("$this->id.a.b", 'b', 50);
        $cache->set("$this->id.a.b.c", 'c', 100);

        sleep(2);
        that($cache)->gc(0.00)->is(0);
        that($cache)->gc(1.00)->isAny([0, 1]); // 0 for redis

        that($cache)->get("$this->id.a")->isNull();
        that($cache)->get("$this->id.a.b")->is('b');
        that($cache)->get("$this->id.a.b.c")->is('c');
    }

    /**
     * @dataProvider provideUrl
     */
    function test_heavy($url, $options)
    {
        $cache = new StreamCache($url, $options);

        $f1KB  = str_repeat(str_repeat('x', 255) . "\n", 4);
        $f1MB  = str_repeat(str_repeat('x', 255) . "\n", 4 * 1024);
        $f10MB = str_repeat(str_repeat('x', 255) . "\n", 4 * 1024 * 10);

        $cache->set("$this->id-1KB", $f1KB);
        $cache->set("$this->id-1MB", $f1MB);
        $cache->set("$this->id-10MB", $f10MB);

        that($cache)->get("$this->id-1KB")->is($f1KB);
        that($cache)->get("$this->id-1MB")->is($f1MB);
        that($cache)->get("$this->id-10MB")->is($f10MB);
    }

    function test_keyfile()
    {
        @mkdir($cachedir = strtr(sys_get_temp_dir() . '/stream-cache', ['\\' => '/']), 0777, true);

        $cache = new StreamCache($cachedir, [
            'directorySupport' => true,
        ]);

        that($cache)->_filename("a.b.c.hoge")->isSame("file://$cachedir/a/b/c/hoge.php");
        that($cache)->_filename("a.b.c.php")->isSame("file://$cachedir/a/b/c.php");
        that($cache)->_filename("a.b.c.php-serialize")->isSame("file://$cachedir/a/b/c.php-serialize");

        that($cache)->_key("file://$cachedir/a/b/c/hoge.php")->isSame("a.b.c.hoge");
        that($cache)->_key("file://$cachedir/a/b/c.php")->isSame("a.b.c");
        that($cache)->_key("file://$cachedir/a/b/c.php-serialize")->isSame("a.b.c");

        $cache = new StreamCache($cachedir, [
            'directorySupport' => false,
        ]);

        that($cache)->_filename("a.b.c.hoge")->isSame("file://$cachedir/a.b.c.hoge.php");
        that($cache)->_filename("a.b.c.php")->isSame("file://$cachedir/a.b.c.php");
        that($cache)->_filename("a.b.c.php-serialize")->isSame("file://$cachedir/a.b.c.php-serialize");

        that($cache)->_key("file://$cachedir/a.b.c.hoge.php")->isSame("a.b.c.hoge");
        that($cache)->_key("file://$cachedir/a.b.c.php")->isSame("a.b.c");
        that($cache)->_key("file://$cachedir/a.b.c.php-serialize")->isSame("a.b.c");
    }

    function test_getExtension()
    {
        that(StreamCache::class)::getExtension('hoge:///example.com')->isSame('com');
        that(StreamCache::class)::getExtension('hoge://example.com')->isSame(null);
        that(StreamCache::class)::getExtension('hoge://example.com/')->isSame(null);
        that(StreamCache::class)::getExtension('hoge://example.com/test.')->isSame('');
        that(StreamCache::class)::getExtension('hoge://example.com/test.txt')->isSame('txt');
        that(StreamCache::class)::getExtension('hoge://example.com/test.txt?a=b.dmy')->isSame('txt');
        that(StreamCache::class)::getExtension('hoge://example.com/test.txt?a=b.dmy#a')->isSame('txt');
        that(StreamCache::class)::getExtension('hoge://example.com/test.txt?a=b.dmy#a.dmy')->isSame('dmy');
        that(StreamCache::class)::getExtension('hoge://example.com/test?a=b.dmy#a')->isSame(null);

        that(StreamCache::class)::getExtension('hoge:///hoge:///')->wasThrown('malformed URL');
    }
}
