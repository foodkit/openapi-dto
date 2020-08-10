<?php

declare(strict_types=1);

namespace Foodkit\OpenApiDto\Builders\TypeBuilders;

class ObjectTypeBuilder extends TypeBuilder
{
    protected $properties = [];

    public function properties(array $properties): ObjectTypeBuilder
    {
        $this->properties = $properties;

        return $this;
    }

    /**
     * @return array
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    public function typeAsString()
    {
        return 'object';
    }
}
