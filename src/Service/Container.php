<?php

namespace Aatis\DependencyInjection\Service;

use Aatis\DependencyInjection\Entity\Service;
use Aatis\DependencyInjection\Exception\DataTypeException;
use Aatis\DependencyInjection\Interface\ContainerInterface;
use Aatis\DependencyInjection\Exception\ServiceNotFoundException;
use Aatis\DependencyInjection\Interface\ServiceInstanciatorInterface;

class Container implements ContainerInterface
{
    /**
     * @var array<string, mixed>
     */
    private array $env = [];

    /**
     * @var array<string, Service>
     */
    private array $services = [];

    private ServiceInstanciatorInterface $serviceInstanciator;

    public function __construct(ServiceInstanciatorInterface $serviceInstanciator)
    {
        $serviceInstanciator->setContainer($this);
        $this->serviceInstanciator = $serviceInstanciator;
    }

    public function get(string $id): mixed
    {
        if (str_starts_with($id, 'APP_')) {
            return $this->env[$id] ?? null;
        }

        if (isset($this->services[$id])) {
            $service = $this->services[$id];

            return $service->getInstance() ?? $this->serviceInstanciator->instanciate($service);
        }

        throw new ServiceNotFoundException(sprintf('Service %s not found', $id));
    }

    /**
     * @return Service[]
     */
    public function getByTag(string $tag): array
    {
        $tagServices = [];

        foreach ($this->services as $service) {
            if (!in_array($tag, $service->getTags())) {
                continue;
            }
            $tagServices[] = $service;
        }

        return $tagServices;
    }

    /**
     * @param string[] $tags
     *
     * @return Service[]
     */
    public function getByTags(array $tags): array
    {
        $tagServices = [];

        foreach ($this->services as $service) {
            if (count(array_intersect($tags, $service->getTags())) !== count($tags)) {
                continue;
            }
            $tagServices[] = $service;
        }

        return $tagServices;
    }

    /**
     * @return Service[]
     */
    public function getByInterface(string $interface): array
    {
        $interfaceServices = [];

        foreach ($this->services as $service) {
            if (!in_array($interface, $service->getInterfaces())) {
                continue;
            }
            $interfaceServices[] = $service;
        }

        return $interfaceServices;
    }

    /**
     * @param string[] $interfaces
     *
     * @return Service[]
     */
    public function getByInterfaces(array $interfaces): array
    {
        $interfaceServices = [];

        foreach ($this->services as $service) {
            if (count(array_intersect($interfaces, $service->getInterfaces())) !== count($interfaces)) {
                continue;
            }
            $interfaceServices[] = $service;
        }

        return $interfaceServices;
    }

    public function set(string $id, mixed $value): void
    {
        if (
            'string' === gettype($value)
            && str_starts_with($id, 'APP_')
        ) {
            $this->env[$id] = $value;

            return;
        }

        if ($value instanceof Service) {
            $this->services[$id] = $value;

            return;
        }

        throw new DataTypeException(sprintf('Can\'t set %s, value is neither a Service nor a env variable', $id));
    }

    /**
     * @param class-string $class
     */
    public function has(string $class): bool
    {
        return isset($this->services[$class]);
    }
}
