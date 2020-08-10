<?php

namespace Foodkit\OpenApiDto\Tests;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

    }

    protected function getPackageProviders($app)
    {
        return [
            //
        ];
    }
}
