<?php

namespace Aatis\DependencyInjection\Component;

use Aatis\ParameterBag;
use Aatis\Tag\Component\Tag;

class ServiceTag extends Tag
{
    public const SERVICE_TARGETED_PREFIX = '@service_of_';
    public const FROM_CLASS_PREFIX = '@from_class_';

    private bool $serviceTargeted;

    private bool $fromClass;

    public function __construct()
    {
        $this->parameters = new ParameterBag();
    }

    public function getName(): string
    {
        return sprintf(
            '%s%s%s%s',
            $this->isServiceTargeted() ? self::SERVICE_TARGETED_PREFIX : '',
            $this->isFromClass() ? self::FROM_CLASS_PREFIX : '',
            self::TAG_PREFIX,
            $this->name
        );
    }

    public function isServiceTargeted(): bool
    {
        return $this->serviceTargeted ?? false;
    }

    public function isFromClass(): bool
    {
        return $this->fromClass ?? false;
    }

    public function setServiceTargeted(bool $serviceTargeted): static
    {
        $this->serviceTargeted = $serviceTargeted;

        return $this;
    }

    public function setFromClass(bool $fromClass): static
    {
        $this->fromClass = $fromClass;

        return $this;
    }
}
