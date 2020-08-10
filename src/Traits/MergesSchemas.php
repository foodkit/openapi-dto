<?php

declare(strict_types=1);

namespace Foodkit\OpenApiDto\Traits;

use Illuminate\Support\Arr;

trait MergesSchemas
{
    /**
     * Merges properties of the given schemas into the definitions.
     *
     * @param array $schemas
     * @param array $overrides
     * @param array $exclude
     * @return array
     */
    public function merge(array $schemas, array $overrides = [], array $exclude = []): array
    {
        $mergedProperties = $overrides;

        foreach ($schemas as $schema) {
            $schemaInstance = new $schema;
            $mergedProperties = array_merge($schemaInstance->properties(), $overrides);
        }

        foreach ($exclude as $itemToExclude) {
            unset($mergedProperties[$itemToExclude]);
        }

        return $mergedProperties;
    }

    /**
     * Includes only the given schema properties.
     *
     * @param string $schema
     * @param array $include
     * @return array
     */
    public function only(string $schema, array $include): array
    {
        $schemaInstance = new $schema;
        return Arr::only($schemaInstance->properties(), $include);
    }
}
