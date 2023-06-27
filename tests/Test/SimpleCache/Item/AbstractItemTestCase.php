<?php

namespace ryunosuke\Test\SimpleCache\Item;

use ryunosuke\SimpleCache\Item\AbstractItem;
use ryunosuke\Test\AbstractTestCase;

abstract class AbstractItemTestCase extends AbstractTestCase
{
    /** @var string|AbstractItem::class */
    protected static $testClass = AbstractItem::class;

    function test_transport()
    {
        $path  = 'file://' . sys_get_temp_dir() . "/$this->id";
        $item1 = new static::$testClass($path);
        $item2 = new static::$testClass($path);

        that($item1)->set('hoge', 1)->isTrue();
        that($item2)->get()->is('hoge');
        that($item1)->delete()->isTrue();
        that($item2)->delete()->isFalse();
    }

    function test_set()
    {
        $path = 'file://' . sys_get_temp_dir() . "/dir/$this->id";
        $item = new static::$testClass($path);

        @that($item)->set('hoge', 1)->isFalse();
    }

    function test_notfound()
    {
        $path = 'file://' . sys_get_temp_dir() . "/$this->id";
        $item = new static::$testClass($path);

        that($item)->get()->isNull();

        file_put_contents($path . '.tmp', "invalid\ndata\n");
        that($item)->get()->isNull();

        that($item)->set('hoge', 1)->isTrue();
        that($item)->get()->isNotNull();
        sleep(1);
        that($item)->get()->isNull();

        that($item)->set('fuga', 10)->isTrue();
        that($item)->get()->isNotNull();
        $item->delete();
        that($item)->get()->isNull();
    }
}
