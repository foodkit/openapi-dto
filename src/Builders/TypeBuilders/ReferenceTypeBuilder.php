<?php

declare(strict_types=1);

namespace Foodkit\OpenApiDto\Builders\TypeBuilders;

class ReferenceTypeBuilder extends TypeBuilder
{
    /**
     * @var string
     */
    protected $schema;

    public function to(string $schema): ReferenceTypeBuilder
    {
        $this->schema = $schema;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getSchema(): string
    {
        return $this->schema;
    }

    public function typeAsString()
    {
        return "object<{$this->schema}>";
    }
}
