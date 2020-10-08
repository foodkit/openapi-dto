<?php

declare(strict_types=1);

namespace Foodkit\OpenApiDto\Facades;

use Illuminate\Support\Facades\Facade;

class OpenApi extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'foodkit.open-api';
    }
}
