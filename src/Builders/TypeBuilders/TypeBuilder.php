<?php

declare(strict_types=1);

namespace Foodkit\OpenApiDto\Builders\TypeBuilders;

abstract class TypeBuilder
{
    /**
     * @var bool
     */
    protected $nullable = false;

    /**
     * @var bool
     */
    protected $hasDefault = false;

    /**
     * @var mixed
     */
    protected $default = null;

    /**
     * @var bool
     */
    protected $required = true;

    /**
     * @return $this
     */
    public function nullable(): TypeBuilder
    {
        $this->nullable = true;

        return $this;
    }

    /**
     * @return bool
     */
    public function isNullable(): bool
    {
        return $this->nullable;
    }

    /**
     * @param mixed $defaultValue
     * @return TypeBuilder
     */
    public function withDefault($defaultValue): TypeBuilder
    {
        $this->hasDefault = true;
        $this->default = $defaultValue;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * @return bool
     */
    public function hasDefault(): bool
    {
        return $this->hasDefault;
    }

    /**
     * @return TypeBuilder
     */
    public function optional(): TypeBuilder
    {
        $this->required = false;

        return $this;
    }

    /**
     * @return bool
     */
    public function isRequired(): bool
    {
        return $this->required;
    }

    abstract public function typeAsString();
}
