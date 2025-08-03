<?php

namespace Aatis\DependencyInjection\Interface;

use Aatis\Tag\Interface\TagBuilderInterface;
use Aatis\Tag\Interface\TagInterface;

interface ServiceSubscriberInterface
{
    /**
     * @template T of TagInterface
     *
     * @param TagBuilderInterface<T> $tagBuilder
     *
     * @return iterable<string>
     */
    public static function getSubscribedServices(TagBuilderInterface $tagBuilder): iterable;
}
