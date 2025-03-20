<?php

namespace Aatis\DependencyInjection\Interface;

use Aatis\DependencyInjection\Component\Service;

interface ServiceInstanciatorInterface
{
    public function setContainer(ContainerInterface $container): void;

    /**
     * @template T of object
     *
     * @param Service<T> $service
     *
     * @return T
     */
    public function instanciate(Service $service): object;
}
