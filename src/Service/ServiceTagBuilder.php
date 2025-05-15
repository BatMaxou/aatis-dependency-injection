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
    protected function newTag(array $options): ServiceTag
    {
        $tag = new ServiceTag();
        if (in_array(ServiceTagOption::SERVICE_TARGETED, $options)) {
            $tag->setServiceTargeted(true);
        }

        return $tag;
    }
}
