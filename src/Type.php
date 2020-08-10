<?php

declare(strict_types=1);

namespace Foodkit\OpenApiDto;

use Foodkit\OpenApiDto\Builders\TypeBuilders\CollectionTypeBuilder;
use Foodkit\OpenApiDto\Builders\TypeBuilders\DatetimeTypeBuilder;
use Foodkit\OpenApiDto\Builders\TypeBuilders\EnumTypeBuilder;
use Foodkit\OpenApiDto\Builders\TypeBuilders\ObjectTypeBuilder;
use Foodkit\OpenApiDto\Builders\TypeBuilders\ReferenceTypeBuilder;
use Foodkit\OpenApiDto\Builders\TypeBuilders\ScalarTypeBuilder;

class Type
{
    public static function reference()
    {
        return new ReferenceTypeBuilder();
    }

    public static function collection()
    {
        return new CollectionTypeBuilder();
    }

    public static function enum()
    {
        return new EnumTypeBuilder();
    }

    public static function object()
    {
        return new ObjectTypeBuilder();
    }

    public static function int()
    {
        return new ScalarTypeBuilder('integer');
    }

    public static function string()
    {
        return new ScalarTypeBuilder('string');
    }

    public static function float()
    {
        return new ScalarTypeBuilder('number');
    }

    public static function bool()
    {
        return new ScalarTypeBuilder('boolean');
    }

    public static function date()
    {
        return (new DatetimeTypeBuilder('date'))->format('Y-m-d');
    }

    public static function datetime()
    {
        return (new DatetimeTypeBuilder('datetime'))->format('Y-m-d H:i:s');
    }
}
