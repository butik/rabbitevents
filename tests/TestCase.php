<?php

namespace Butik\Events\Tests;

class TestCase extends \PHPUnit\Framework\TestCase
{
    public function tearTown()
    {
        \Mockery::close();
    }
}
