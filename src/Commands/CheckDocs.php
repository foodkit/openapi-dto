<?php

declare(strict_types=1);

namespace Foodkit\OpenApiDto\Commands;

use Foodkit\OpenApiDto\Resolvers\SpecsResolver;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Routing\Route;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class CheckDocs extends BaseDocsCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'foodkit:check-docs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks API routes coverage based on OpenAPI docs';

    protected const EXIT_CODE_VALIDATION_ERROR = 1;
    protected const EXIT_CODE_COVERAGE_ERROR = 1;

    /** @var SpecsResolver */
    protected $specsResolver;

    /**
     * CheckDocs constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->specsResolver = new SpecsResolver();
    }

    /**
     * Execute the console command.
     *
     * @throws FileNotFoundException
     */
    public function handle(): int
    {
        $version = $this->ask('What API version would you like to check?', '*');
        if (!$this->isValidApiVersion($version)) {
            $this->error("The version you provided is not valid. It can either be any version (\"*\") or a specific version (the latest one is {$this->latestApiVersion}.0).");
            return self::EXIT_CODE_VALIDATION_ERROR;
        }

        $covered = true;

        if ($version === '*') {
            //Only integer versioning is currently supported. If point releases are introduced (e.g. v5, v5.3) use a map of versions
            for ($version = 1; $version < $this->latestApiVersion + 1; $version++) {
                $this->line("Checking version {$version}...");
                if (!$this->checkDocs($version)) {
                    $covered = false;
                }
            }
        } elseif (!$this->checkDocs((int) $version)) {
            $covered = false;
        }

        return $covered ? 0 : self::EXIT_CODE_COVERAGE_ERROR;
    }

    /**.
     * Resolves the routes based on version,
     * checks them against specs and OpenAPI documentation, and generates coverage report.
     *
     * @param int $version
     * @return bool
     * @throws FileNotFoundException
     */
    public function checkDocs(int $version): bool
    {
        $versionedRoutes = $this->docsResolver->resolveRoutesForVersion($version);
        $groupedRoutes = $this->docsResolver->groupRoutes($versionedRoutes);

        $coverage = [
            'documented' => 0,
            'annotated' => 0,
            'missing' => 0
        ];
        $documented = true;

        foreach ($groupedRoutes as $uri => $routesGroup) {
            $groupedDocs = $this->docsManager->loadDocs($uri);
            $groupedRoutesCoverage = $this->checkGroupedDocs($groupedDocs, $routesGroup);

            $coverage['documented'] += $groupedRoutesCoverage['documented'];
            $coverage['annotated'] += $groupedRoutesCoverage['annotated'];
            $coverage['missing'] += $groupedRoutesCoverage['missing'];
        }

        if ($coverage['missing'] > 0) {
            $documented = false;
        }

        $this->displayCoverageReport($coverage, $versionedRoutes);

        return $documented;
    }

    /**
     * Checks the given routes against OpenAPI documentation and generates coverage report.
     *
     * @param array $docs
     * @param Collection $routes
     * @return array
     */
    public function checkGroupedDocs(array $docs, Collection $routes): array
    {
        $annotatedRoutesCount = 0; // routes that have annotations
        $documentedRoutesCount = 0; // routes that have docs entries generated from the annotations

        foreach ($routes as $route) {
            /** @var Route $route */
            if (!$this->routeIsAnnotated($route)) {
                $this->warn("[".$this->docsResolver->resolveDocsPathMethod($route)."] ".$this->docsResolver->resolveDocsPathUri($route).' route is not annotated');
                continue;
            }

            if ($this->routeIsDocumented($route, $docs)) {
                $documentedRoutesCount++;
            } else {
                $annotatedRoutesCount++;
            }
        }

        $notCoveredRoutesCounts = $routes->count() - $annotatedRoutesCount - $documentedRoutesCount;

        return [
            'documented' => $documentedRoutesCount,
            'annotated' => $annotatedRoutesCount,
            'missing' => $notCoveredRoutesCounts,
        ];
    }

    /**
     * @param array $coverage
     * @param Collection $routes
     */
    public function displayCoverageReport(array $coverage, Collection $routes): void
    {
        $coverage = [
            'documented' => "{$coverage['documented']} (".number_format(($coverage['documented'] / $routes->count()) * 100, 0)."%)",
            'annotated' => "{$coverage['annotated']} (".number_format(($coverage['annotated'] / $routes->count()) * 100, 0)."%)",
            'missing' => "{$coverage['missing']} (".number_format(($coverage['missing'] / $routes->count()) * 100, 0)."%)",
        ];

        $this->table(['Documented', 'Annotated', 'Missing'], [$coverage]);
    }


    /**
     * Determines, if the given route has specs annotations set.
     *
     * @param Route $route
     * @return bool
     */
    protected function routeIsAnnotated(Route $route): bool
    {
        $requestSpecs = $this->specsResolver->resolveRequestSpecs($route);
        $responseSpecs = $this->specsResolver->resolveResponseSpecs($route);

        return $requestSpecs && $responseSpecs;
    }

    /**
     * Determines, if the given route has been fully documented.
     *
     * @param Route $route
     * @param array $docs
     * @return bool
     */
    protected function routeIsDocumented(Route $route, array $docs): bool
    {
        $routePathDescriptor = Arr::get($docs['paths'], $this->docsResolver->resolveDocsPathUri($route));

        return $routePathDescriptor && Arr::has($routePathDescriptor, $this->docsResolver->resolveDocsPathMethod($route));
    }
}
