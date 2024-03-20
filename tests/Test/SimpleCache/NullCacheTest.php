<?php

namespace ryunosuke\Test\SimpleCache;

use ryunosuke\SimpleCache\NullCache;
use ryunosuke\Test\AbstractTestCase;

class NullCacheTest extends AbstractTestCase
{
    function test_all()
    {
        $cache = new NullCache(true, true);

        that($cache)->set("$this->id-1", '1')->is(true);
        that($cache)->has("$this->id-1")->is(false);
        that($cache)->get("$this->id-1")->is(null);
        that($cache)->delete("$this->id-1")->is(true);
        that($cache)->clear()->is(true);

        that($cache)->lock($this->id, LOCK_EX)->is(false);

        that($cache)->keys()->is([]);
        that($cache)->items()->is([]);

        that($cache)->gc(1)->is(0);
    }

    function test_enabledSlashKey()
    {
        $cache = new NullCache(true);

        that($cache)->get('ho/ge', 'notfound')->is('notfound');
        that($cache)->set('ho/ge', 'notfound')->is('notfound');
        that($cache)->delete('ho/ge')->is('notfound');

        $cache = new NullCache(false);

        that($cache)->get('ho/ge', 'notfound')->wasThrown('contains reserved character');
        that($cache)->set('ho/ge', 'notfound')->wasThrown('contains reserved character');
        that($cache)->delete('ho/ge')->wasThrown('contains reserved character');
    }

    function test_affectedReturnValue()
    {
        foreach ([true, false] as $affectedReturnValue) {
            $cache = new NullCache(false, $affectedReturnValue);

            that($cache)->has('hoge')->is(false);
            that($cache)->clear()->is($affectedReturnValue);

            that($cache)->get('hoge', 'notfound')->is('notfound');
            that($cache)->set('hoge', 'HOGE')->is($affectedReturnValue);
            that($cache)->delete('hoge')->is($affectedReturnValue);

            that($cache)->getMultiple(['hoge', 'fuga'], 'notfound')->is([
                "hoge" => "notfound",
                "fuga" => "notfound",
            ]);
            that($cache)->setMultiple(['hoge' => 'HOGE', 'fuga' => 'FUGA'], 123)->is($affectedReturnValue);
            that($cache)->deleteMultiple(['hoge', 'fuga'])->is($affectedReturnValue);
        }
    }
}
