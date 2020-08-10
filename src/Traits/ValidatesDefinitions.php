<?php

declare(strict_types=1);

namespace Foodkit\OpenApiDto\Traits;

use Foodkit\OpenApiDto\Builders\TypeBuilders\CollectionTypeBuilder;
use Foodkit\OpenApiDto\Builders\TypeBuilders\ObjectTypeBuilder;
use Foodkit\OpenApiDto\Definition;
use InvalidArgumentException;

trait ValidatesDefinitions
{
    /**
     * Checks, if the given definitions have been correctly specified.
     *
     * @param array $definitions
     * @param string $type
     * @param string|null $source
     */
    protected function validateDefinitions(array $definitions, string $type, string $source = null): void
    {
        if (!$source) {
            $source = static::class;
        }

        foreach ($definitions as $definitionName => $definition) {
            if (!($definition instanceof Definition)) {
                throw new InvalidArgumentException("\"{$definitionName}\" {$type} parameter in {$source} has invalid definition.");
            }

            if ($definition->getType() instanceof ObjectTypeBuilder) {
                $this->validateDefinitions($definition->getType()->getProperties(), 'property', $source);
            }

            if ($definition->getType() instanceof CollectionTypeBuilder && $definition->getType()->getItemType() instanceof ObjectTypeBuilder) {
                $this->validateDefinitions($definition->getType()->getItemType()->getProperties(), 'property', $source);
            }
        }
    }
}
