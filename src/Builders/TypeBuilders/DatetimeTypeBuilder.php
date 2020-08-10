<?php

declare(strict_types=1);

namespace Foodkit\OpenApiDto\Builders\TypeBuilders;

class DatetimeTypeBuilder extends TypeBuilder
{
    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $format;

    public function __construct(string $type)
    {
        $this->type = $type;
    }

    /**
     * @param null $format
     * @return DatetimeTypeBuilder
     */
    public function format($format): DatetimeTypeBuilder
    {
        $this->format = $format;

        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getFormat(): string
    {
        return $this->format;
    }

    public function typeAsString()
    {
        return 'datetime';
    }
}
