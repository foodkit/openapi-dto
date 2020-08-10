<?php

declare(strict_types=1);

namespace Foodkit\OpenApiDto\Builders\TypeBuilders;

class EnumTypeBuilder extends TypeBuilder
{
    /**
     * @var array
     */
    protected $values;

    public function values(array $values): EnumTypeBuilder
    {
        $this->values = $values;

        return $this;
    }

    /**
     * @return array
     */
    public function getValues(): array
    {
        return $this->values;
    }

    public function typeAsString()
    {
        return 'enum<'.implode(', ', $this->getValues()).'>';
    }
}
