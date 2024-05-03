<?php

namespace ryunosuke\Test\SimpleCache;

use Psr\SimpleCache\CacheInterface;
use ryunosuke\SimpleCache\ChainCache;
use ryunosuke\SimpleCache\Contract\FetchableInterface;
use ryunosuke\SimpleCache\Contract\FetchTrait;
use ryunosuke\SimpleCache\Contract\MultipleTrait;
use ryunosuke\SimpleCache\StreamCache;
use ryunosuke\Test\AbstractTestCase;

class ChainCacheTest extends AbstractTestCase
{
    function test_all()
    {
        $url    = self::emptyDirectory();
        $cache1 = new StreamCache("$url/1", ['defaultTtl' => 2]);
        $cache2 = new StreamCache("$url/2", ['defaultTtl' => 2]);
        $cache  = new ChainCache([$cache1, $cache2]);

        that($cache)->get('hoge', 'notfound')->is('notfound');
        that($cache)->set('hoge', 'HOGE')->is(true);
        that($cache)->set('fuga', 'FUGA')->is(true);
        that($cache)->set('piyo', 'PIYO')->is(true);

        that($cache)->has('hoge')->is(true);
        that($cache1)->has('hoge')->is(true);
        that($cache2)->has('hoge')->is(true);

        that($cache)->get('hoge', 'notfound')->is('HOGE');
        that($cache1)->get('hoge', 'notfound')->is('HOGE');
        that($cache2)->get('hoge', 'notfound')->is('HOGE');

        that($cache)->delete('hoge')->is(true);
        that($cache)->get('hoge', 'notfound')->is('notfound');
        that($cache1)->get('hoge', 'notfound')->is('notfound');
        that($cache2)->get('hoge', 'notfound')->is('notfound');

        $cache1->delete('fuga');
        that($cache)->get('fuga', 'notfound')->is('FUGA');
        that($cache1)->get('fuga', 'notfound')->is('FUGA');
        that($cache2)->get('fuga', 'notfound')->is('FUGA');

        that($cache)->clear()->is(true);
        that($cache)->get('piyo', 'notfound')->is('notfound');
        that($cache1)->get('piyo', 'notfound')->is('notfound');
        that($cache2)->get('piyo', 'notfound')->is('notfound');

        that($cache)->has('piyo')->is(false);
        that($cache1)->has('piyo')->is(false);
        that($cache2)->has('piyo')->is(false);

        $cache1->set('hogera', 'hogera1');
        $cache2->set('hogera', 'hogera2');
        that($cache)->get('hogera', 'notfound')->is('hogera1');
        that($cache1)->get('hogera')->is('hogera1');
        that($cache2)->get('hogera')->is('hogera2');

        $cache1->set('hogera1', 'hogera1');
        $cache2->set('hogera2', 'hogera2');
        that($cache)->has('hogera1')->is(true);
        that($cache)->has('hogera2')->is(true);
        that($cache1)->has('hogera2')->is(false);
        that($cache2)->has('hogera1')->is(false);
        that($cache)->getMultiple(['hogera1', 'hogera2', 'hogera3'], 'notfound')->is([
            "hogera1" => "hogera1",
            "hogera2" => "hogera2",
            "hogera3" => "notfound",
        ]);
        that($cache1)->has('hogera2')->is(true);
        that($cache2)->has('hogera1')->is(false);
        that($cache1)->has('hogera3')->is(false);
        that($cache2)->has('hogera3')->is(false);

        that($cache)->fetch('hogera3', fn() => 'hogera3')->is('hogera3');
        that($cache1)->has('hogera3')->is(true);
        that($cache2)->has('hogera3')->is(true);

        that($cache)->keys()->is(["hogera", "hogera1", "hogera2", "hogera3"], null, true);
        sleep(2);
        that($cache)->gc(1, 3)->is(7);
        that($cache)->keys()->is([]);
    }

    function test_misc()
    {
        $dummy = new class() implements CacheInterface, FetchableInterface {
            use MultipleTrait;
            use FetchTrait;

            public function get($key, $default = null): mixed { return $default; }

            public function set($key, $value, $ttl = null): bool { return true; }

            public function delete($key): bool { return true; }

            public function clear(): bool { return true; }

            public function has($key): bool { return false; }
        };
        $cache = new ChainCache([$dummy]);

        that($cache)->get($this->id)->is(null);
        that($dummy)->fetch($this->id, fn() => 'hoge')->is('hoge');

        that($cache)->lock($this->id, LOCK_EX)->is(false);
    }
}
