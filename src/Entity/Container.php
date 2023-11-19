<?php

namespace Aatis\DependencyInjection\Entity;

use Psr\Container\ContainerInterface;
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

    public function get(string $class): object
    {
        if (self::class === $class) {
            return $this;
        }

        if (!isset($this->services[$class])) {
            throw new ServiceNotFoundException('Service not found');
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

    public function has(string $class): bool
    {
        return isset($this->services[$class]);
    }

    public function set(string $class, Service $service): void
    {
        $this->services[$class] = $service;
    }
}
