<?php

declare(strict_types=1);

namespace Foodkit\OpenApiDto\Commands;

use Foodkit\OpenApiDto\Builders\DocsBuilder;
use Foodkit\OpenApiDto\Facades\OpenApi;
use Foodkit\OpenApiDto\Resolvers\SpecsResolver;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use RuntimeException;
use SplFileInfo;

class MergeSpecs extends BaseDocsCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'foodkit:merge-specs {--base-url=https://dev.foodkit.io/api}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Merges generated specs into single files by modifier.';

    /** @var DocsBuilder $docsBuilder */
    protected $docsBuilder;

    /** @var array $specsHeap */
    protected $specsHeap;

    /** @var string $baseUrl */
    protected $baseUrl;

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
     * @return mixed
     * @throws FileNotFoundException
     */
    public function handle()
    {
        $version = $this->ask('For which API version would you like to merge the specs?', '*');
        if (!$this->isValidApiVersion($version)) {
            $this->error("The version you provided is not valid. It can either be any version (\"*\") or a specific version (the latest one is {$this->latestApiVersion}.0).");
            return;
        }

        $this->baseUrl = $this->option('base-url');
        if (!$this->baseUrl) {
            $this->error("Base API url cannot be empty.");
            return;
        }

        $this->specsHeap = $this->buildDocsHeap();

        if ($version === '*') {
            //Only integer versioning is currently supported. If point releases are introduced (e.g. v5, v5.3) use a map of versions
            for ($version = 1; $version < $this->latestApiVersion + 1; $version++) {
                $this->mergeSpecs($version);
            }
        } else {
            $this->mergeSpecs((int) $version);
        }
    }

    /**
     * Merges generated docs into single files by modifier.
     *
     * @param int $version
     */
    protected function mergeSpecs(int $version): void
    {
        $mergeCallback = OpenApi::getMergeCallback();

        if (!$mergeCallback) {
            throw new RuntimeException('Please use OpenApi::setMergeCallback to set the callback for merging Open API specs.');
        }

        $specs = $mergeCallback($this->specsHeap, $version);
        $specs = $this->prefixMergedDocs($specs);

        $this->saveSpecs($specs, $version);

        $this->info("Specs for API version {$version} has been merged.");
    }

    /**
     * Adds api/v<version> prefix to the path specs.
     *
     * @param array $mergedDocs
     * @return array
     */
    protected function prefixMergedDocs(array $mergedDocs): array
    {
        $docs = [];

        foreach ($mergedDocs as $modifier => $paths) {
            if (!Arr::has($docs, $modifier)) {
                $docs[$modifier] = [];
            }

            foreach ($paths as $pathUri => $operationDescriptors) {
                foreach ($operationDescriptors as $operationMethod => $operationDescriptor) {
                    $routeUri = "/v{$operationDescriptor['api_version']}/{$pathUri}";
                    unset($operationDescriptor['api_version']);

                    if (!Arr::has($docs[$modifier], $routeUri)) {
                        $docs[$modifier][$routeUri] = [];
                    }

                    $docs[$modifier][$routeUri] = array_merge($docs[$modifier][$routeUri], [$operationMethod => $operationDescriptor]);
                }
            }
        }

        return $docs;
    }

    /**
     * Generates a list of all api specs grouped by api version.
     *
     * @return array
     * @throws FileNotFoundException
     */
    protected function buildDocsHeap(): array
    {
        $docsFiles = File::allFiles(base_path("docs"));
        $docsHeap = [];

        foreach ($docsFiles as $docsFile) {
            /** @var SplFileInfo $docsFile */
            if (in_array($docsFile->getFilename(), ['private.json', 'public.json'])) {
                continue;
            }

            $docs = json_decode(File::get($docsFile->getRealPath()), true);

            if (!is_array($docs)) {
                continue;
            }

            $pathUris = array_keys($docs);

            foreach ($pathUris as $pathUri) {
                $pathOperationDescriptors = Arr::get($docs, $pathUri);

                if (!is_array($pathOperationDescriptors)) {
                    continue;
                }

                $pathApiVersion = $this->resolvePathUriVersion($pathUri);

                if (!Arr::has($docsHeap, $pathApiVersion)) {
                    $docsHeap[$pathApiVersion] = [];
                }

                foreach ($pathOperationDescriptors as $operationMethod => &$operationDescriptor) {
                    $operationDescriptor['api_version'] = $pathApiVersion;
                }

                $pathUriWithoutVersion = $this->resolvePathUriWithoutVersion($pathUri);
                $docsHeap[$pathApiVersion][$pathUriWithoutVersion] = $pathOperationDescriptors;

            }
        }

        return $docsHeap;
    }

    /**
     * @param string $pathUri
     * @return string
     */
    protected function resolvePathUriWithoutVersion(string $pathUri): string
    {
        [, , $unversionedPath] = $this->resolvePathUriComponents($pathUri);
        return $unversionedPath;
    }

    /**
     * @param string $pathUri
     * @return int
     */
    protected function resolvePathUriVersion(string $pathUri): int
    {
        [, $version,] = $this->resolvePathUriComponents($pathUri);

        return (int) $version;
    }

    /**
     * Resolves path uri version and relative path.
     *
     * @param string $pathUri
     * @return array
     */
    protected function resolvePathUriComponents(string $pathUri): array
    {
        preg_match('/^\/api\/v(\d)\/([\w\/\-_{}?]+)/', $pathUri, $matches);
        return $matches;
    }

    /**
     * Saves public and private docs for the given version.
     *
     * @param array $mergedSpecs
     * @param int $version
     * @throws BindingResolutionException
     */
    protected function saveSpecs(array $mergedSpecs, int $version): void
    {
        $saveCallback = OpenApi::getMergedSpecsSaveCallback();

        if (!$saveCallback) {
            throw new RuntimeException('Please use OpenApi::setMergedSpecsSaveCallback to set the callback for merging Open API specs.');
        }

        $specsRoot = $this->docsBuilder->buildDocsRoot($version, $this->baseUrl);

        $saveCallback($mergedSpecs, $specsRoot, $version, $this->docsManager);
    }
}
