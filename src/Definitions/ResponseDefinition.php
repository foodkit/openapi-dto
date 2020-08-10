<?php

declare(strict_types=1);

namespace Foodkit\OpenApiDto\Definitions;

use Foodkit\OpenApiDto\Traits\MergesSchemas;
use Foodkit\OpenApiDto\Traits\ValidatesDefinitions;

abstract class ResponseDefinition
{
    use MergesSchemas, ValidatesDefinitions;

    /**
     * Defines whether to treat body parameters as a single item in a returned array.
     *
     * @var bool $collection
     */
    protected $collection = false;

    public function __construct()
    {
        $this->validateDefinitions($this->bodyParameters(), 'body');
        $this->validateDefinitions($this->headers(), 'header');
        $this->validateDefinitions($this->cookies(), 'cookie');
    }

    public function bodyParameters(): array
    {
        return [];
    }

    public function headers(): array
    {
        return [];
    }

    public function cookies(): array
    {
        return [];
    }

    public function responseCode(): int
    {
        return 200;
    }

    public function description(): string
    {
        return '';
    }

    /**
     * @return bool
     */
    public function isCollection(): bool
    {
        return $this->collection;
    }
}
