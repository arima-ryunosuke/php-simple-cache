<?php

namespace ryunosuke\Test\SimpleCache;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ryunosuke\SimpleCache\Item\AbstractItem;
use ryunosuke\SimpleCache\StreamCache;
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
        $dataset['file'] = [$cachedir, []];

        if (REDIS_URL) {
            $dataset['redis'] = [REDIS_URL, []];
        }
        if (MYSQL_URL) {
            $dataset['mysql'] = [MYSQL_URL, []];
        }
        if (S3_URL) {
            $dataset['s3'] = [S3_URL, ['directorySupport' => true]];
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
    function test_withNamespace($url, $options)
    {
        $cache    = new StreamCache($url, $options);
        $subcache = $cache->withNamespace('subcache');

        $subcache->clear();
        $subcache->set($this->id, 'subitem');

        that($subcache)->get($this->id)->is('subitem');
        if (that($cache)->directorySupport->return()) {
            that($cache)->keys()->contains("subcache/$this->id");
        }
    }

    /**
     * @dataProvider provideUrl
     */
    function test_memorize($url, $options)
    {
        $cache = new StreamCache($url, $options + ['memorize' => true]);

        that($cache)->set($this->id, 'abc')->isTrue();
        that($cache)->get($this->id)->is('abc');
        that($cache)->items->hasKey($this->id);
        that($cache)->delete($this->id)->isTrue();
        that($cache)->items->notHasKey($this->id);

        $cache = new StreamCache($url, $options + ['memorize' => false]);

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
            "$this->id/x" => 'x',
            "$this->id/y" => 'y',
            "$this->id/z" => 'z',
        ])->isTrue();

        that($cache)->getMultiple([
            "$this->id/x",
            "$this->id/z",
        ])->is([
            "$this->id/x" => 'x',
            "$this->id/z" => 'z',
        ]);

        that($cache)->deleteMultiple([
            "$this->id/x",
            "$this->id/y",
        ])->isTrue();

        that($cache)->has("$this->id/x")->isFalse();
        that($cache)->has("$this->id/y")->isFalse();
        that($cache)->has("$this->id/z")->isTrue();

        that($cache)->fetchMultiple([
            "$this->id/x" => fn() => 'X',
            "$this->id/y" => fn() => 'Y',
            "$this->id/z" => fn() => 'Z',
        ])->is([
            "$this->id/x" => 'X',
            "$this->id/y" => 'Y',
            "$this->id/z" => 'z',
        ]);
    }

    /**
     * @dataProvider provideUrl
     */
    function test_fetch($url, $options)
    {
        $cache = new StreamCache($url, $options);

        $cache->deleteMultiple([
            "$this->id/x",
            "$this->id/x/y",
            "$this->id/x/y/z",
        ]);

        that($cache)->fetch("$this->id/x", fn() => 'aX')->is('aX');
        that($cache)->fetch("$this->id/x/y", fn() => 'bX')->is('bX');
        that($cache)->fetch("$this->id/x/y/z", fn() => 'cX')->is('cX');
    }

    /**
     * @dataProvider provideUrl
     */
    function test_clear($url, $options)
    {
        $cache = new StreamCache($url, $options);

        $cache->set($this->id, 'X');
        $cache->set("$this->id/a", 'a');
        $cache->set("$this->id/a/b", 'b');
        $cache->set("$this->id/a/b/c", 'c');

        that($cache)->clear()->isTrue();
        that($cache)->has($this->id)->isFalse();
        that($cache)->has("$this->id/a")->isFalse();
        that($cache)->has("$this->id/a/b")->isFalse();
        that($cache)->has("$this->id/a/b/c")->isFalse();
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

        $cache->set("$this->id/a", 'a');
        $cache->set("$this->id/a/b", 'b');
        $cache->set("$this->id/a/b/c", 'c');

        that($cache)->keys("$this->id/a/b*")->is(["$this->id/a/b", "$this->id/a/b/c"], null, true);
        that($cache)->items("$this->id/a/b*")->eachIsInstanceOf(AbstractItem::class);
    }

    /**
     * @dataProvider provideUrl
     */
    function test_gc($url, $options)
    {
        $cache = new StreamCache($url, $options);

        $cache->clear();
        $cache->set("$this->id/a", 'a', 1);
        $cache->set("$this->id/a/b", 'b', 50);
        $cache->set("$this->id/a/b/c", 'c', 100);

        sleep(2);
        that($cache)->gc(0.00)->is(0);
        that($cache)->gc(1.00)->isAny([0, 1]); // 0 for redis

        that($cache)->get("$this->id/a")->isNull();
        that($cache)->get("$this->id/a/b")->is('b');
        that($cache)->get("$this->id/a/b/c")->is('c');
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
