<?php

declare(strict_types=1);

namespace Foodkit\OpenApiDto;

use Illuminate\Support\ServiceProvider;

class OpenApiDtoServiceProvider extends ServiceProvider
{
    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('foodkit.open-api', OpenApi::class);
    }

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
    }
}
