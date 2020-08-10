<?php

declare(strict_types=1);

namespace Foodkit\OpenApiDto;

use Foodkit\OpenApiDto\Builders\TypeBuilders\TypeBuilder;

class Definition
{
    /**
     * @var TypeBuilder
     */
    private $type;

    /**
     * @var string|null
     */
    private $description;

    /**
     * @var DefinitionExample|null $example
     */
    private $examples;

    /**
     * @var bool $deprecated
     */
    private $deprecated;

    /**
     * Definition constructor.
     * @param TypeBuilder $type
     * @param string|null $description
     * @param DefinitionExample[] $examples
     * @param bool $deprecated
     */
    public function __construct(
        TypeBuilder $type,
        ?string $description = '',
        $examples = [],
        bool $deprecated = false
    ) {
        $this->type = $type;
        $this->description = $description;
        $this->examples = $examples;
        $this->deprecated = $deprecated;
    }

    public static function of(TypeBuilder $type): Definition
    {
        return new Definition($type);
    }

    /**
     * @param TypeBuilder $type
     * @return Definition
     */
    public function ofType(TypeBuilder $type): Definition
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return TypeBuilder
     */
    public function getType(): TypeBuilder
    {
        return $this->type;
    }

    /**
     * @param string|null $description
     * @return Definition
     */
    public function withDescription(?string $description): Definition
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param mixed $value
     * @return Definition
     */
    public function withExample($value): Definition
    {
        $this->examples['default'] = new DefinitionExample($value);
        return $this;
    }

    /**
     * @param array $examples
     * @return $this
     */
    public function setExamples(array $examples): Definition
    {
        $this->examples = $examples;
        return $this;
    }

    /**
     * @return DefinitionExample[]
     */
    public function getExamples(): array
    {
        return $this->examples;
    }

    /**
     * @return bool
     */
    public function hasExamples(): bool
    {
        return count($this->getExamples()) > 0;
    }

    /**
     * @param bool $deprecated
     * @return Definition
     */
    public function deprecated(bool $deprecated = true): Definition
    {
        $this->deprecated = $deprecated;

        return $this;
    }

    /**
     * @return bool
     */
    public function isDeprecated(): bool
    {
        return $this->deprecated;
    }
}
