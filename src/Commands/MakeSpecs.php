<?php

declare(strict_types=1);

namespace Foodkit\OpenApiDto\Commands;

use Foodkit\OpenApiDto\Resolvers\RoutesResolver;
use Illuminate\Console\Command;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeSpecs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'foodkit:make-specs {--action=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates test case, request and response spec stubs.';

    protected $docsResolver;

    /**
     * MakeSpecs constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->docsResolver = new RoutesResolver(collect(\Illuminate\Support\Facades\Route::getRoutes()->getRoutes()));
    }

    /**
     * Execute the console command.
     *
     */
    public function handle()
    {
        $action = $this->option('action');

        if (!$action) {
            $this->error('Controller action has to be specified in order to generate the stubs.');
            return;
        }

        [$controller, $method] = explode('@', $action);

        if (!class_exists($controller)) {
            $this->error('The action controller you provided is not correct.');
            return;
        }

        if (!$route = $this->resolveRoute($action)) {
            $this->error('The action route is not registered.');
            return;
        }

        $baseName = $this->resolveBaseName($controller, $method);
        $contextNamespace = $this->resolveContextNamespace($route);
        $version = $this->resolveVersion($route);

        $this->generateTestStub($baseName, $contextNamespace, $version, $action);
        $this->generateRequestStub($baseName, $contextNamespace, $version);
        $this->generateResponseStub($baseName, $contextNamespace, $version);
    }

    /**
     * @param string $controller
     * @param string $method
     * @return string
     */
    public function resolveBaseName(string $controller, string $method): string
    {
        return str_replace('Controller', '', class_basename($controller)).Str::studly($method);
    }

    public function resolveRoute(string $action): ?Route
    {
        return \Illuminate\Support\Facades\Route::getRoutes()->getByAction($action);
    }

    public function resolveContextNamespace(Route $route): string
    {
        $path = $this->docsResolver->prepareUri($route->uri());
        preg_match('/v\d\/([-\w\/_]+)/', $path, $matches);

        return collect(explode('/', $matches[1]))->map(function (string $pathPart) {
            return Str::studly($pathPart);
        })->implode('\\');
    }

    public function resolveVersion(Route $route): int
    {
        preg_match('/v(\d)/', $route->uri(), $matches);

        return (int) $matches[1];
    }

    public function generateTestStub(string $baseName, string $contextNamespace, int $version, string $action)
    {
        $namespace = "FoodkitTests\\Specs\\Tests\\v{$version}\\{$contextNamespace}";
        $class = "{$baseName}Test";

        $contextPath = $this->convertNamespaceToPath($contextNamespace);
        $filePath = base_path("tests/Specs/Tests/v{$version}/{$contextPath}/{$baseName}Test.php");

        $this->makeStub(
            __DIR__.'/../Stubs/TestCaseStub.php.stub',
            $filePath,
            ['#NAMESPACE#' => $namespace, '#CLASS_NAME#' => $class, '#ACTION_NAME#' => $action]
        );
    }

    public function generateRequestStub(string $baseName, string $contextNamespace, int $version)
    {
        $namespace = "Foodkit\\Specs\\v{$version}\\Requests\\{$contextNamespace}";
        $class = "{$baseName}RequestDefinition";

        $contextPath = $this->convertNamespaceToPath($contextNamespace);
        $filePath = base_path("specs/v{$version}/Requests/{$contextPath}/{$baseName}RequestDefinition.php");

        $this->makeStub(
            __DIR__.'/../Stubs/RequestDefinitionStub.php.stub',
            $filePath,
            ['#NAMESPACE#' => $namespace, '#CLASS_NAME#' => $class]
        );
    }

    public function generateResponseStub(string $baseName, string $contextNamespace, int $version)
    {
        $namespace = "Foodkit\\Specs\\v{$version}\\Responses\\{$contextNamespace}";
        $class = "{$baseName}ResponseDefinition";

        $contextPath = $this->convertNamespaceToPath($contextNamespace);
        $filePath = base_path("specs/v{$version}/Responses/{$contextPath}/{$baseName}ResponseDefinition.php");

        $this->makeStub(
            __DIR__.'/../Stubs/ResponseDefinitionStub.php.stub',
            $filePath,
            ['#NAMESPACE#' => $namespace, '#CLASS_NAME#' => $class]
        );
    }

    public function makeStub(string $stubPath, string $filePath, array $parameters): void
    {
        $stubContent = File::get($stubPath);

        foreach ($parameters as $parameterName => $parameterValue) {
            $stubContent = str_replace($parameterName, $parameterValue, $stubContent);
        }

        $fileDirectory = dirname($filePath);
        if (!File::exists($fileDirectory)) {
            File::makeDirectory($fileDirectory, 493, true);
        }
        File::put($filePath, $stubContent);

        $this->info("Generated stub: {$filePath}");
    }

    public function convertNamespaceToPath(string $namespace): string
    {
        return str_replace('\\', '/', $namespace);
    }
}
