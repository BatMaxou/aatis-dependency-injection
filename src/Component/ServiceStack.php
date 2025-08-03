<?php

namespace Aatis\DependencyInjection\Component;

use Aatis\DependencyInjection\Exception\ServiceNotFoundException;
use Aatis\DependencyInjection\Interface\ServiceInstanciatorInterface;
use Aatis\DependencyInjection\Trait\ContainerTrait;
use Psr\Container\ContainerInterface;

class ServiceStack implements ContainerInterface
{
    use ContainerTrait {
        get as getService;
    }

    /**
     * @param array<string, Service<object>> $services
     */
    public function __construct(ServiceInstanciatorInterface $serviceInstanciator, array $services = [])
    {
        $this->serviceInstanciator = $serviceInstanciator;
        $this->services = $services;
    }

    public function get(string $id): mixed
    {
        [$result] = $this->getService($id);
        if (null !== $result) {
            return $result;
        }

        throw new ServiceNotFoundException(sprintf('Service %s not found', $id));
    }

    /**
     * @param class-string $class
     */
    public function has(string $class): bool
    {
        return isset($this->services[$class]);
    }
}
