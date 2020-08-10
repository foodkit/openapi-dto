<?php

declare(strict_types=1);

namespace Foodkit\OpenApiDto\Builders\TypeBuilders;

class CollectionTypeBuilder extends TypeBuilder
{
    /**
     * @var TypeBuilder
     */
    protected $itemType;

    public function of(TypeBuilder $itemType): CollectionTypeBuilder
    {
        $this->itemType = $itemType;

        return $this;
    }

    /**
     * @return TypeBuilder
     */
    public function getItemType(): TypeBuilder
    {
        return $this->itemType;
    }

    public function typeAsString()
    {
        return "array<{$this->itemType->typeAsString()}>";
    }
}
