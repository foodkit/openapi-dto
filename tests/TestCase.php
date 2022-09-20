<?php

namespace Foodkit\OpenApiDto\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected $loadEnvironmentVariables = true;
}
