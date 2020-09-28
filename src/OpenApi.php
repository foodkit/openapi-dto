<?php

declare(strict_types=1);

namespace Foodkit\OpenApiDto;

class OpenApi
{
    /** @var array $tags */
    protected $tags = [];

    /** @var callable|null $mergeCallback */
    protected $mergeCallback;

    /** @var callable|null $mergedSpecsSaveCallback */
    protected $mergedSpecsSaveCallback;

    /**
     * @param array $tags
     * @return OpenApi
     */
    public function setTags(array $tags): OpenApi
    {
        $this->tags = $tags;
        return $this;
    }

    /**
     * @return array
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * @param callable $mergeCallback
     * @return OpenApi
     */
    public function setMergeCallback(callable $mergeCallback): OpenApi
    {
        $this->mergeCallback = $mergeCallback;
        return $this;
    }

    /**
     * @return callable|null
     */
    public function getMergeCallback(): ?callable
    {
        return $this->mergeCallback;
    }

    /**
     * @param callable|null $mergedSpecsSaveCallback
     * @return OpenApi
     */
    public function setMergedSpecsSaveCallback(?callable $mergedSpecsSaveCallback): OpenApi
    {
        $this->mergedSpecsSaveCallback = $mergedSpecsSaveCallback;
        return $this;
    }

    /**
     * @return callable|null
     */
    public function getMergedSpecsSaveCallback(): ?callable
    {
        return $this->mergedSpecsSaveCallback;
    }
}
