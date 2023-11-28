<?php

namespace Aatis\DependencyInjection\Entity;

use Aatis\DependencyInjection\Interface\ContainerInterface;
use Aatis\DependencyInjection\Exception\ServiceNotFoundException;

class Container implements ContainerInterface
{
    /**
     * @var array<string, Service>
     */
    private array $services = [];

    public function __construct()
    {
        Service::setContainer($this);
    }

    /**
     * @param class-string $class
     */
    public function get(string $class): object
    {
        if (self::class === $class) {
            return $this;
        }

        if (!isset($this->services[$class])) {
            throw new ServiceNotFoundException(sprintf('Service %s not found', $class));
        }

        return $this->services[$class]->getInstance();
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

    /**
     * @param class-string $class
     */
    public function has(string $class): bool
    {
        return isset($this->services[$class]);
    }

    public function set(string $class, Service $service): void
    {
        $this->services[$class] = $service;
    }
}
