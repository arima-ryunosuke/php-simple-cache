<?php

namespace ryunosuke\Test;

use PHPUnit\Framework\TestCase;
use ryunosuke\PHPUnit\TestCaseTrait;

class AbstractTestCase extends TestCase
{
    use TestCaseTrait;

    protected string $id;

    protected function setUp(): void
    {
        parent::setUp();

        $this->id = $this->getName(false);
    }
}
