<?php

declare(strict_types=1);

namespace Foodkit\OpenApiDto;

use Foodkit\OpenApiDto\Traits\ValidatesDefinitions;

abstract class Schema
{
    use ValidatesDefinitions;

    abstract public function properties(): array;

    public function __construct()
    {
        $this->validateDefinitions($this->properties(), 'property');
    }
}
