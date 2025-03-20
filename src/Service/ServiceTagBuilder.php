<?php

namespace Aatis\DependencyInjection\Service;

use Aatis\DependencyInjection\Component\ServiceTag;
use Aatis\DependencyInjection\Enum\ServiceTagOption;
use Aatis\Tag\Service\TagBuilder;

/**
 * @extends TagBuilder<ServiceTag>
 */
class ServiceTagBuilder extends TagBuilder
{
    /**
     * @param class-string[] $interfaces
     * @param \BackedEnum[] $options
     *
     * @return ServiceTag[]|string[]
     */
    public function buildFromInterfaces(array $interfaces, $options = []): array
    {
        $tags = [];

        foreach ($interfaces as $interface) {
            $tags[] = $this->buildFromInterface($interface, $options);
        }

        return array_unique($tags);
    }

    /**
     * @param class-string $interface
     * @param \BackedEnum[] $options
     */
    public function buildFromInterface(string $interface, $options = []): ServiceTag|string
    {
        $explode = explode('\\', $interface);
        $interface = $explode[count($explode) - 1];
        if (str_ends_with($interface, 'Interface')) {
            $interface = substr($interface, 0, -9);
        }

        $tag = implode(array_map(
            fn ($letter) => ctype_upper($letter) ? sprintf('-%s', strtolower($letter)) : $letter,
            str_split(lcfirst($interface)),
        ));

        return $this->buildFromName($tag, $options);
    }

    protected function newTag(array $options): ServiceTag
    {
        $tag = new ServiceTag();
        if (in_array(ServiceTagOption::SERVICE_TARGETED, $options)) {
            $tag->setServiceTargeted(true);
        }

        return $tag;
    }
}
