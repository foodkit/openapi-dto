<?php

declare(strict_types=1);

namespace Foodkit\OpenApiDto\Builders\TypeBuilders;

class ScalarTypeBuilder extends TypeBuilder
{
    protected $type;

    public function __construct(string $type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    public function typeAsString()
    {
        return $this->getType();
    }
}
