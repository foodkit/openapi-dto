<?php

declare(strict_types=1);

namespace Foodkit\OpenApiDto\Resolvers;

use Foodkit\OpenApiDto\Definitions\RequestDefinition;
use Foodkit\OpenApiDto\Definitions\ResponseDefinition;
use Illuminate\Routing\Route;
use ReflectionException;

class SpecsResolver
{
    /**
     * @param Route $route
     * @return RequestDefinition|null
     */
    public function resolveRequestSpecs(Route $route): ?RequestDefinition
    {
        return $this->resolveSpecs('request', $route);
    }

    /**
     * @param Route $route
     * @return ResponseDefinition|null
     */
    public function resolveResponseSpecs(Route $route): ?ResponseDefinition
    {
        return $this->resolveSpecs('response', $route);
    }

    /**
     * @param string $type
     * @param Route $route
     * @return RequestDefinition|ResponseDefinition||null
     */
    protected function resolveSpecs(string $type, Route $route)
    {
        $actionDescriptor = explode('@', $route->getActionName());
        if ($actionDescriptor[0] === 'Closure') {
            return null;
        }

        [$controller, $method] = $actionDescriptor;

        if (!class_exists($controller)) {
            return null;
        }

        try {
            $reflectedController = new \ReflectionClass($controller);
            $reflectedMethod = $reflectedController->getMethod($method);
        } catch (ReflectionException $exception) {
            //TODO: log undefined methods?
            return null;
        }

        $methodDocBlock = $reflectedMethod->getDocComment();
        if (!is_string($methodDocBlock)) {
            return null;
        }

        preg_match("/@{$type}Spec\s+([\w\\\]+)$/mi", $methodDocBlock, $matches);

        if (count($matches) > 0) {
            [, $specsClass] = $matches;
            return class_exists($specsClass) ? new $specsClass : null; //TODO: use named group?
        }

        return null;
    }
}
