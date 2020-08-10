<?php

declare(strict_types=1);

namespace Foodkit\OpenApiDto\Commands;

use Foodkit\OpenApiDto\Builders\DocsBuilder;
use Foodkit\OpenApiDto\Resolvers\SpecsResolver;

class BuildSpecs extends BaseDocsCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'foodkit:build-specs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Builds Open API compatible specs from app\'s route definitions';

    /** @var DocsBuilder $docsBuilder */
    protected $docsBuilder;

    /**
     * BuildDocs constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->docsBuilder = new DocsBuilder($this->docsResolver, new SpecsResolver());
    }

    /**
     * Execute the console command.
     *
     * @throws \Exception
     */
    public function handle()
    {
        $version = $this->ask('For which API version would you like to generate the specs?', '*');
        if (!$this->isValidApiVersion($version)) {
            $this->error("The version you provided is not valid. It can either be any version (\"*\") or a specific version (the latest one is {$this->latestApiVersion}.0).");
            return;
        }

        $this->docsManager->deleteDocs();

        if ($version === '*') {
            //Only integer versioning is currently supported. If point releases are introduced (e.g. v5, v5.3) use a map of versions
            for ($version = 1; $version < $this->latestApiVersion + 1; $version++) {
                $this->buildDocs($version);
            }
        } else {
            $this->buildDocs((int) $version);
        }
    }

    /**
     * Generates OpenAPI documentation for the given API version.
     *
     * @param int $version
     * @throws \Exception
     */
    protected function buildDocs(int $version): void
    {
        $versionedRoutes = $this->docsResolver->resolveRoutesForVersion($version);
        $groupedRoutes = $this->docsResolver->groupRoutes($versionedRoutes);

        foreach ($groupedRoutes as $uri => $routes) {
            $docs = $this->docsBuilder->buildDocs($routes);

            if (!count($docs)) {
                continue;
            }

            $this->docsManager->saveDocs($docs, $uri);
        }

        $this->info("OpenAPI specifications for API version {$version} have been generated.");
    }
}
