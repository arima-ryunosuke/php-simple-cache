<?php

namespace ryunosuke\Test\SimpleCache\Exception;

use DateInterval;
use ryunosuke\SimpleCache\Exception\InvalidArgumentException;
use ryunosuke\Test\AbstractTestCase;

class InvalidArgumentExceptionTest extends AbstractTestCase
{
    function test_normalizeKeyOrThrow()
    {
        that(InvalidArgumentException::class)::normalizeKeyOrThrow('this-is-valid/key')->wasThrown('contains reserved character');
        that(InvalidArgumentException::class)::normalizeKeyOrThrow('')->wasThrown('is empty string');
        that(InvalidArgumentException::class)::normalizeKeyOrThrow('{placeholder}')->wasThrown('contains reserved character');
    }

    function test_normalizeTtlOrThrow()
    {
        that(InvalidArgumentException::class)::normalizeTtlOrThrow(null)->is(null);
        that(InvalidArgumentException::class)::normalizeTtlOrThrow(123)->is(123);
        that(InvalidArgumentException::class)::normalizeTtlOrThrow(new DateInterval('PT1M2S'))->is(62);
    }
}
