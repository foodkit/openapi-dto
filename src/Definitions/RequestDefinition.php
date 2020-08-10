<?php

declare(strict_types=1);

namespace Foodkit\OpenApiDto\Definitions;

use Foodkit\OpenApiDto\Traits\MergesSchemas;
use Foodkit\OpenApiDto\Traits\ValidatesDefinitions;

abstract class RequestDefinition
{
    use MergesSchemas, ValidatesDefinitions;

    public const MODIFIER_PUBLIC = 'modifier.public';
    public const MODIFIER_PRIVATE = 'modifier.private';
    public const MODIFIER_SKIP = 'modifier.skip';

    public function __construct()
    {
        $this->validateDefinitions($this->bodyParameters(), 'body');
        $this->validateDefinitions($this->pathParameters(), 'path');
        $this->validateDefinitions($this->queryParameters(), 'query');
        $this->validateDefinitions($this->headers(), 'header');
        $this->validateDefinitions($this->cookies(), 'cookie');
    }

    public function bodyParameters(): array
    {
        return [];
    }

    public function pathParameters(): array
    {
        return [];
    }

    public function queryParameters(): array
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

    public function summary(): string
    {
        return '';
    }

    public function description(): string
    {
        return '';
    }

    public function optionalBody(): bool
    {
        return false;
    }

    public function deprecated(): bool
    {
        return false;
    }

    public function modifier(): string
    {
        return self::MODIFIER_PUBLIC;
    }

    abstract public function tags(): array;
}
