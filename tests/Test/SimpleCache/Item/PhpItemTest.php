<?php

namespace ryunosuke\Test\SimpleCache\Item;

use ryunosuke\SimpleCache\Exception\CacheException;
use ryunosuke\SimpleCache\Item\PhpItem;

class PhpItemTest extends AbstractItemTestCase
{
    protected static $testClass = PhpItem::class;
    protected static $extension = 'php';

    function test_export()
    {
        $path = 'file://' . sys_get_temp_dir() . "/$this->id" . '.php';
        $item = new static::$testClass($path);

        $item->set(function () { return 123; }, 1);
        that($item->get())()->is(123);
    }

    function test_fallback()
    {
        $path = 'file://' . sys_get_temp_dir() . "/$this->id" . '.php';
        $item = new static::$testClass($path);

        $item->delete();

        file_put_contents($path, <<<'PHP'
        <?php
        if (!isset($GLOBALS['first'])) {
            $GLOBALS['first'] = true;
            echo $undefined;
        }
        return 'contents';
        PHP,);

        that($item)->get()->isNull();
        that($item)->delete()->isTrue();
    }

    function test_unknown()
    {
        $path = 'file://' . sys_get_temp_dir() . "/$this->id" . '.php';
        $item = new static::$testClass($path);

        $item->delete();

        file_put_contents($path, <<<'PHP'
        <?php
        throw new \RuntimeException();
        PHP,);

        that($item)->get()->wasThrown(CacheException::class);
    }
}
