<?php

declare(strict_types=1);

namespace Foodkit\OpenApiDto;

class DefinitionExample
{
    /** @var mixed $value */
    protected $value;

    /**
     * DefinitionExample constructor.
     *
     * @param $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * @param mixed $value
     * @return DefinitionExample
     */
    public function setValue($value): DefinitionExample
    {
        $this->value = $value;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }
}
