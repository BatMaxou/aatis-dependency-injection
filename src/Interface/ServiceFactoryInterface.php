<?php

namespace Aatis\DependencyInjection\Interface;

use Aatis\DependencyInjection\Component\Service;

interface ServiceFactoryInterface
{
    /**
     * @template T of object
     *
     * @param class-string<T> $namespace
     *
     * @return Service<T>
     */
    public function create(string $namespace): Service;
}
