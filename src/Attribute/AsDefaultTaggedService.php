<?php

namespace Aatis\DependencyInjection\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
class AsDefaultTaggedService
{
    /**
     * @param string[] $subjects
     */
    public function __construct(private array $subjects = [])
    {
    }

    /**
     * @return string[]
     */
    public function getSubjects(): array
    {
        return $this->subjects;
    }
}
