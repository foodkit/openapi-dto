<?php

declare(strict_types=1);

namespace Foodkit\OpenApiDto\Resolvers;

use Illuminate\Routing\Route;
use Illuminate\Support\Collection;

class RoutesResolver
{
    /** @var Collection|Route[] $routes */
    protected $routes;

    /**
     * DocsResolver constructor.
     * @param Collection $routes
     */
    public function __construct(Collection $routes)
    {
        $this->routes = $routes;
    }

    /**
     * Retrieves API routes for the given version.
     *
     * @param int $version
     * @return Collection
     */
    public function resolveRoutesForVersion(int $version): Collection
    {
        return $this->routes->filter(function (Route $route) use ($version) {
            return strpos($route->uri(), "api/v{$version}") !== false
                || $this->isAuthRoute($route);
        })->values();
    }

    public function groupRoutes(Collection $routes): Collection
    {
        $groupedRoutes = collect();

        foreach ($routes as $route) {
            $preparedUri = $this->prepareUri($route->uri());

            if (!$groupedRoutes->has($preparedUri)) {
                $groupedRoutes[$preparedUri] = collect();
            }

            $groupedRoutes[$preparedUri]->push($route);
        }

        return $groupedRoutes;
    }

    public function prepareUri(string $uri): string
    {
        // remove /api prefix
        $uri = preg_replace('/^api/', '', $uri);
        // remove path parameters
        $uri = preg_replace('/\/{\w*\??}/', '', $uri);

        return trim($uri, '/');
    }

    /**
     * Builds OpenAPI path uri from the given route.
     *
     * @param Route $route
     * @return string
     */
    public function resolveDocsPathUri(Route $route): string
    {
        return '/'.$route->uri();
    }

    /**
     * Retrieves and formats route method for OpenAPI path descriptor.
     *
     * @param Route $route
     * @return string
     */
    public function resolveDocsPathMethod(Route $route): string
    {
        return strtolower($route->methods()[0]);
    }

    /**
     * Determines, if the given route is related to authentication.
     *
     * @param Route $route
     * @return bool
     */
    public function isAuthRoute(Route $route): bool
    {
        return strpos($route->uri(), '/v1/oauth') !== false || strpos($route->uri(), '/otp') !== false;
    }
}
