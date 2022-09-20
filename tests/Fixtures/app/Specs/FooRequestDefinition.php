<?php

namespace Foodkit\OpenApiDto\Tests\Fixtures\App\Specs;

use Foodkit\OpenApiDto\Definitions\RequestDefinition;

class FooRequestDefinition extends RequestDefinition
{
    public function tags() : array
    {
        return [];
    }
}
