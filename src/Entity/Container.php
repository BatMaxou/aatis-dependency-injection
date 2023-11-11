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

    public function has(string $class): bool
    {
        return isset($this->services[$class]);
    }

    public function set(string $class, Service $service): void
    {
        $this->services[$class] = $service;
    }
}
