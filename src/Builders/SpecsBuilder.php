<?php

declare(strict_types=1);

namespace Foodkit\OpenApiDto\Builders;

use Carbon\Carbon;
use Exception;
use Foodkit\OpenApiDto\Builders\TypeBuilders\CollectionTypeBuilder;
use Foodkit\OpenApiDto\Builders\TypeBuilders\DatetimeTypeBuilder;
use Foodkit\OpenApiDto\Builders\TypeBuilders\EnumTypeBuilder;
use Foodkit\OpenApiDto\Builders\TypeBuilders\ObjectTypeBuilder;
use Foodkit\OpenApiDto\Builders\TypeBuilders\ReferenceTypeBuilder;
use Foodkit\OpenApiDto\Builders\TypeBuilders\ScalarTypeBuilder;
use Foodkit\OpenApiDto\Builders\TypeBuilders\TypeBuilder;
use Foodkit\OpenApiDto\Definition;
use Foodkit\OpenApiDto\Definitions\RequestDefinition;
use Foodkit\OpenApiDto\Definitions\ResponseDefinition;
use Foodkit\OpenApiDto\Facades\OpenApi;
use Foodkit\OpenApiDto\Resolvers\RoutesResolver;
use Foodkit\OpenApiDto\Resolvers\SpecsResolver;
use Illuminate\Routing\Route;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use RuntimeException;

class SpecsBuilder
{
    /**
     * @var RoutesResolver $docsResolver
     */
    protected $docsResolver;

    /**
     * @var SpecsResolver $specsResolver
     */
    protected $specsResolver;

    /**
     * DocsBuilder constructor.
     *
     * @param RoutesResolver $docsResolver
     * @param SpecsResolver $specsResolver
     */
    public function __construct(RoutesResolver $docsResolver, SpecsResolver $specsResolver)
    {
        $this->docsResolver = $docsResolver;
        $this->specsResolver = $specsResolver;
    }

    /**
     * Generates OpenAPI root descriptor.
     *
     * @param int $version
     * @param string $baseUrl
     * @return array
     */
    public function buildDocsRoot(int $version, string $baseUrl): array
    {
        return [
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'Foodkit API', //TODO(final stage): add description field
                'version' => "{$version}.0",
            ],
            'servers' => [
                [
                    'url' => $baseUrl
                ]
            ],
            'tags' => OpenApi::getTags()
        ];
    }

    /**
     * Generates OpenAPI paths descriptors from the given routes collection.
     *
     * @param Collection $routes
     * @return array
     * @throws Exception
     */
    public function buildSpecs(Collection $routes): array
    {
        $paths = [];

        foreach ($routes as $route) {
            /** @var Route $route */
            $requestSpecs = $this->specsResolver->resolveRequestSpecs($route);
            $responseSpecs = $this->specsResolver->resolveResponseSpecs($route);

            if (!$requestSpecs || !$responseSpecs) {
                continue;
            }

            $routePathUri = $this->docsResolver->resolveDocsPathUri($route);

            $this->validatePathParameters($routePathUri, $requestSpecs);

            if (!array_key_exists($routePathUri, $paths)) {
                $paths[$routePathUri] = [];
            }

            $routePathMethod = $this->docsResolver->resolveDocsPathMethod($route);
            $paths[$routePathUri][$routePathMethod] = $this->buildSpecsPath($requestSpecs, $responseSpecs, $routePathMethod);
        }

        return $paths;
    }

    /**
     * Generates OpenAPI path descriptor from the given specs.
     *
     * @param RequestDefinition $requestSpec
     * @param ResponseDefinition $responseSpec
     * @param string $routePathMethod
     * @return array
     */
    public function buildSpecsPath(RequestDefinition $requestSpec, ResponseDefinition $responseSpec, string $routePathMethod): array
    {
        $pathDescriptor = [
            'summary' => $requestSpec->summary(),
            'description' => $requestSpec->description(),
            'parameters' => $this->buildSpecsPathParameters($requestSpec),
            'responses' => $this->buildSpecsPathResponses($responseSpec),
            'tags' => $this->buildSpecsPathTags($requestSpec)
        ];

        if ($routePathMethod !== 'get') {
            $pathDescriptor['requestBody'] = $this->buildSpecsPathRequestBody($requestSpec);
        }

        if ($requestSpec->deprecated()) {
            $pathDescriptor['deprecated'] = true;
        }

        ksort($pathDescriptor);

        return $pathDescriptor;
    }

    /**
     * Generates OpenAPI path responses from the given specs.
     *
     * @param ResponseDefinition $responseSpec
     * @return array
     */
    public function buildSpecsPathResponses(ResponseDefinition $responseSpec): array
    {
        $headers = $this->buildSpecsPathResponseHeaders($responseSpec);

        $descriptor = [
            'description' => $responseSpec->description(),
            'content' => [
                'application/json' => [
                    'schema' => []
                ]
            ]
        ];

        if ($responseSpec->isCollection()) {
            $descriptor['content']['application/json']['schema'] = [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'properties' => $this->buildSchemaProperties($responseSpec->bodyParameters())
                ]
            ];
        } else {
            $descriptor['content']['application/json']['schema'] = [
                'type' => 'object',
                'properties' => $this->buildSchemaProperties($responseSpec->bodyParameters())
            ];
        }

        if (count($headers)) {
            $descriptor['headers'] = $headers;
        }

        return [
            $responseSpec->responseCode() => $descriptor
        ];
    }

    /**
     * Generates OpenAPI path tags from the given specs.
     *
     * @param RequestDefinition $requestSpec
     * @return array
     */
    public function buildSpecsPathTags(RequestDefinition $requestSpec): array
    {
        return $requestSpec->tags();
    }

    /**
     * Generates OpenAPI path response headers from the given specs.
     *
     * @param ResponseDefinition $responseSpec
     * @return array
     */
    protected function buildSpecsPathResponseHeaders(ResponseDefinition $responseSpec): array
    {
        return collect($responseSpec->headers())->mapWithKeys(function (Definition $parameterDefinition, string $parameterName) {
            return [
                $parameterName => [
                    'description' => $parameterDefinition->getDescription(),
                    'schema' => $this->buildParameterSchema($parameterDefinition)
                ]
            ];
        })->toArray();
    }

    /**
     * Generates OpenAPI request body from the given specs.
     *
     * @param RequestDefinition $requestSpec
     * @return array
     */
    public function buildSpecsPathRequestBody(RequestDefinition $requestSpec): array
    {
        $properties = $this->buildSchemaProperties($requestSpec->bodyParameters());

        return [
            'required' => !$requestSpec->optionalBody(),
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => $properties
                    ]
                ]
            ]
        ];
    }

    /**
     * Generates OpenAPI path parameters descriptors from the given route.
     *
     * @param RequestDefinition $requestSpec
     * @return Collection
     */
    public function buildSpecsPathParameters(RequestDefinition $requestSpec): Collection
    {
        $pathParameters = collect($requestSpec->pathParameters())->map(function (Definition $parameterDefinition, string $parameterName) {
            return $this->buildSpecsPathParameter($parameterName, $parameterDefinition, 'path');
        });
        $queryParameters = collect($requestSpec->queryParameters())->map(function (Definition $parameterDefinition, string $parameterName) {
            return $this->buildSpecsPathParameter($parameterName, $parameterDefinition, 'query');
        });
        $headerParameters = collect($requestSpec->headers())->map(function (Definition $parameterDefinition, string $parameterName) {
            return $this->buildSpecsPathParameter($parameterName, $parameterDefinition, 'header');
        });
        $cookieParameters = collect($requestSpec->cookies())->map(function (Definition $parameterDefinition, string $parameterName) {
            return $this->buildSpecsPathParameter($parameterName, $parameterDefinition, 'cookie');
        });

        return $pathParameters->merge($queryParameters)->merge($headerParameters)->merge($cookieParameters)->values();
    }

    /**
     * Generates OpenAPI path parameter descriptor from the given definition.
     *
     * @param string $parameterName
     * @param Definition $parameterDefinition
     * @param string $in
     * @return array
     */
    public function buildSpecsPathParameter(string $parameterName, Definition $parameterDefinition, string $in): array
    {
        $parameterDescriptor = [
            'in' => $in,
            'name' => $parameterName,
            'description' => $parameterDefinition->getDescription(),
            'schema' => $this->buildParameterSchema($parameterDefinition),
        ];

        if ($parameterDefinition->hasExamples()) {
            $parameterDescriptor['example'] = Arr::first($parameterDefinition->getExamples())->getValue();
        }

        if ($parameterDefinition->getType()->isRequired()) {
            $parameterDescriptor['required'] = true;
        }

        if ($parameterDefinition->isDeprecated()) {
            $parameterDefinition['deprecated'] = true;
        }

        return $parameterDescriptor;
    }

    /**
     * Coverts the given definition into its OpenAPI representation.
     *
     * @param Definition $definition
     * @return array
     */
    public function buildParameterSchema(Definition $definition): array
    {
        $typeBuilder = $definition->getType();

        $commonSchema = [
            'type' => $this->resolveSchemaType($definition->getType())
        ];

        if ($typeBuilder->hasDefault()) {
            $commonSchema['default'] = $typeBuilder->getDefault();
        }

        switch (true) {
            case $typeBuilder instanceof ReferenceTypeBuilder:
                $schema = $this->buildReferenceTypeSchema($typeBuilder);
                break;
            case $typeBuilder instanceof EnumTypeBuilder:
                $schema = $this->buildEnumTypeSchema($typeBuilder);
                break;
            case $typeBuilder instanceof CollectionTypeBuilder:
                $schema = $this->buildCollectionTypeSchema($typeBuilder);
                break;
            case $typeBuilder instanceof DatetimeTypeBuilder:
                $schema = $this->buildDatetimeTypeSchema($typeBuilder);
                break;
            default:
                $schema = [];
                break;
        }

        return array_merge($commonSchema, $schema);
    }

    /**
     * Converts the given definition type(builder) into its OpenAPI representation.
     *
     * @param TypeBuilder $typeBuilder
     * @return string
     */
    protected function resolveSchemaType(TypeBuilder $typeBuilder): string
    {
        switch (true) {
            case $typeBuilder instanceof ScalarTypeBuilder:
                return $typeBuilder->getType();
                break;
            case $typeBuilder instanceof ReferenceTypeBuilder:
            case $typeBuilder instanceof ObjectTypeBuilder:
                return 'object';
                break;
            case $typeBuilder instanceof DatetimeTypeBuilder:
            case $typeBuilder instanceof EnumTypeBuilder:
                return 'string';
                break;
            case $typeBuilder instanceof CollectionTypeBuilder:
                return 'array';
                break;
            default:
                throw new RuntimeException(get_class($typeBuilder).' builder is not supported.');
                break;
        }
    }

    /**
     * Checks, if all path parameters of the given uri have been defined in the route specs.
     *
     * @param string $pathUri
     * @param RequestDefinition $requestDefinition
     * @throws Exception
     */
    public function validatePathParameters(string $pathUri, RequestDefinition $requestDefinition): void
    {
        preg_match_all('/({[\w?\-]+})/', $pathUri, $matches);
        $extractedParameters = $matches[1];
        $definedParameters = array_keys($requestDefinition->pathParameters());

        foreach ($extractedParameters as $extractedParameter) {
            if (!in_array(str_replace(['{', '}'], '', $extractedParameter), $definedParameters, true)) {
                throw new Exception("{$extractedParameter} path parameter in \"{$pathUri}\" route is not defined in path parameters of the specs (".get_class($requestDefinition).").");
            }
        }
    }

    /**
     * @param ReferenceTypeBuilder $typeBuilder
     * @return array
     */
    protected function buildReferenceTypeSchema(ReferenceTypeBuilder $typeBuilder): array
    {
        $schema = $typeBuilder->getSchema();

        return [
            'type' => 'object',
            'properties' => $this->buildSchemaProperties((new $schema)->properties())
        ];
    }

    /**
     * @param array $properties
     * @return array
     */
    protected function buildSchemaProperties(array $properties): array
    {
        return collect($properties)->mapWithKeys(function (Definition $propertyDefinition, string $propertyName) {
            $propertyDescriptor = [
                'type' => $this->resolveSchemaType($propertyDefinition->getType()),
                'description' => $propertyDefinition->getDescription(),
            ];

            if ($propertyDefinition->hasExamples()) {
                $propertyDescriptor['example'] = Arr::first($propertyDefinition->getExamples())->getValue();
            }

            if ($propertyDefinition->getType()->hasDefault()) {
                $propertyDescriptor['default'] = $propertyDefinition->getType()->getDefault();
            }

            if ($propertyDefinition->isDeprecated()) {
                $propertyDescriptor['deprecated'] = true;
            }

            $propertyTypeBuilder = $propertyDefinition->getType();
            if ($propertyTypeBuilder instanceof ReferenceTypeBuilder || $propertyTypeBuilder instanceof ObjectTypeBuilder) {
                $propertyDescriptor = $this->buildNestedSchemaProperties($propertyDescriptor, $propertyDefinition);
            }

            if ($propertyDefinition->getType() instanceof CollectionTypeBuilder) {
                $propertyDescriptor = $this->buildArraySchemaProperties($propertyDescriptor, $propertyDefinition);
            }

            if ($propertyDefinition->getType() instanceof EnumTypeBuilder) {
                $propertyDescriptor['enum'] = $propertyDefinition->getType()->getValues();
            }

            return [$propertyName => $propertyDescriptor];
        })->toArray();
    }

    /**
     * @param array $propertyDescriptor
     * @param Definition $propertyDefinition
     * @return array
     */
    protected function buildNestedSchemaProperties(array $propertyDescriptor, Definition $propertyDefinition): array
    {
        if ($propertyDefinition->getType() instanceof ReferenceTypeBuilder) {
            $schema = $propertyDefinition->getType()->getSchema();
            $properties = (new $schema)->properties();
        } elseif ($propertyDefinition->getType() instanceof ObjectTypeBuilder) {
            $properties = $propertyDefinition->getType()->getProperties();
        } else {
            throw new InvalidArgumentException('The given definition has unsupported nested properties type builder.');
        }

        $propertyDescriptor['properties'] = $this->buildSchemaProperties($properties);

        return $propertyDescriptor;
    }

    /**
     * @param array $propertyDescriptor
     * @param Definition $propertyDefinition
     * @return array
     */
    protected function buildArraySchemaProperties(array $propertyDescriptor, Definition $propertyDefinition): array
    {
        $propertyDescriptor['items'] = [
            'type' => $this->resolveSchemaType($propertyDefinition->getType()->getItemType())
        ];

        $itemType = $propertyDefinition->getType()->getItemType();

        if ($itemType instanceof ReferenceTypeBuilder) {
            $schema = $propertyDefinition->getType()->getItemType()->getSchema();
            $propertyDescriptor['items']['properties'] = $this->buildSchemaProperties((new $schema)->properties());
        }

        if ($itemType instanceof ObjectTypeBuilder) {
            $propertyDescriptor['items']['properties'] = $this->buildSchemaProperties($propertyDefinition->getType()->getItemType()->getProperties());
        }

        return $propertyDescriptor;
    }

    /**
     * @param EnumTypeBuilder $typeBuilder
     * @return array
     */
    protected function buildEnumTypeSchema(EnumTypeBuilder $typeBuilder): array
    {
        return [
            'enum' => $typeBuilder->getValues()
        ];
    }

    /**
     * @param CollectionTypeBuilder $typeBuilder
     * @return array
     */
    protected function buildCollectionTypeSchema(CollectionTypeBuilder $typeBuilder): array
    {
        return [
            'items' => [
                'type' => $this->resolveSchemaType($typeBuilder->getItemType()),
            ]
        ];
    }

    /**
     * @param DatetimeTypeBuilder $typeBuilder
     * @return array
     */
    protected function buildDatetimeTypeSchema(DatetimeTypeBuilder $typeBuilder): array
    {
        return [
            'format' => $typeBuilder->getType() === 'datetime' ? 'date-time' : 'date'
        ];
    }
}
