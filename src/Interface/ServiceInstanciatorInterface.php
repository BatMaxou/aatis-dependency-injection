<?php

namespace Aatis\DependencyInjection\Interface;

use Aatis\DependencyInjection\Component\Service;

interface ServiceInstanciatorInterface
{
    public function setContainer(ContainerInterface $container): void;

    public function instanciate(Service $service): object;
}
