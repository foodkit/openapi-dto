<?php

namespace Foodkit\OpenApiDto\Tests;

use Foodkit\OpenApiDto\Resolvers\SpecsResolver;
use Foodkit\OpenApiDto\Tests\Fixtures\App\Specs\FooRequestDefinition;
use Illuminate\Support\Facades\Route;

class SpecsResolverTest extends TestCase
{
    protected function setUp() : void
    {
        parent::setUp();

        $this->resolver = new SpecsResolver;
    }

    /** @test */
    public function it_can_resolve_request_specs_with_string_syntax_route_action()
    {
        $route = Route::get('foo', 'Foodkit\OpenApiDto\Tests\Fixtures\App\Http\Controllers\FooController@__invoke');

        $result = $this->resolver->resolveRequestSpecs($route);

        $this->assertInstanceOf(FooRequestDefinition::class, $result);
    }

    /** @test */
    public function it_can_resolve_request_specs_with_array_syntax_route_action()
    {
        $route = Route::get('foo', ['Foodkit\OpenApiDto\Tests\Fixtures\App\Http\Controllers\FooController', '__invoke']);

        $result = $this->resolver->resolveRequestSpecs($route);

        $this->assertInstanceOf(FooRequestDefinition::class, $result);
    }

    /** @test */
    public function it_can_resolve_request_specs_with_invokable_route_action()
    {
        $route = Route::get('foo', \Foodkit\OpenApiDto\Tests\Fixtures\App\Http\Controllers\FooController::class);

        $result = $this->resolver->resolveRequestSpecs($route);

        $this->assertInstanceOf(FooRequestDefinition::class, $result);
    }
}
