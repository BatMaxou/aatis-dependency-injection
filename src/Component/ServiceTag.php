<?php

namespace Aatis\DependencyInjection\Component;

use Aatis\ParameterBag;
use Aatis\Tag\Component\Tag;

class ServiceTag extends Tag
{
    public const SERVICE_TARGETED_PREFIX = '@service_of_tag_';

    private bool $serviceTargeted;

    public function __construct()
    {
        $this->parameters = new ParameterBag();
    }

    public function getName(): string
    {
        return sprintf(
            '%s%s',
            $this->isServiceTargeted()
                ? self::SERVICE_TARGETED_PREFIX
                : self::TAG_PREFIX,
            $this->name
        );
    }

    public function isServiceTargeted(): bool
    {
        return $this->serviceTargeted ?? false;
    }

    public function setServiceTargeted(bool $serviceTargeted): static
    {
        $this->serviceTargeted = $serviceTargeted;

        return $this;
    }
}
