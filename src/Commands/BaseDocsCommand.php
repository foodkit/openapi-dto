<?php

declare(strict_types=1);

namespace Foodkit\OpenApiDto\Commands;

use Foodkit\OpenApiDto\Managers\DocsManager;
use Foodkit\OpenApiDto\Resolvers\RoutesResolver;
use Illuminate\Console\Command;
use Illuminate\Routing\Route;

abstract class BaseDocsCommand extends Command
{
    /** @var int $latestApiVersion */
    protected $latestApiVersion = 7;

    /** @var \Illuminate\Support\Collection|Route[] $routes */
    protected $routes;

    /** @var RoutesResolver $routesResolver */
    protected $routesResolver;

    /** @var DocsManager $docsManager */
    protected $docsManager;

    public function __construct()
    {
        parent::__construct();

        $this->routes = collect(\Illuminate\Support\Facades\Route::getRoutes()->getRoutes());
        $this->routesResolver = new RoutesResolver($this->routes);
        $this->docsManager = new DocsManager();
    }

    protected function isValidApiVersion($version): bool
    {
        // Only integer versioning is currently supported. If point releases are introduced (e.g. v5, v5.3) use a map of versions
        return $version === '*' || (is_numeric($version) && $version >= 1 && $version <= $this->latestApiVersion);
    }
}
