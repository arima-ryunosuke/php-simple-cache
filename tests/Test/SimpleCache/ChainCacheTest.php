<?php

namespace ryunosuke\Test\SimpleCache;

use ryunosuke\SimpleCache\ChainCache;
use ryunosuke\SimpleCache\StreamCache;
use ryunosuke\Test\AbstractTestCase;

class ChainCacheTest extends AbstractTestCase
{
    function test_all()
    {
        $url    = self::emptyDirectory();
        $cache1 = new StreamCache("$url/1");
        $cache2 = new StreamCache("$url/2");
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
    }
}
