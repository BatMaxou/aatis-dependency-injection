<?php

namespace Aatis\DependencyInjection\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
class AsDefaultTaggedService
{
    /**
     * @param string[] $tags
     */
    public function __construct(private array $tags = [])
    {
    }

    /**
     * @return string[]
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    public function hasTag(string $tag): bool
    {
        return in_array($tag, $this->tags, true);
    }
}
