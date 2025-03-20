<?php

namespace Aatis\DependencyInjection\Interface;

use Psr\Container\ContainerInterface as PsrContainerInterface;

interface ContainerInterface extends PsrContainerInterface
{
    /**
     * Add a service to the container.
     *
     * @param string $id id of the data to add
     * @param mixed $service Data to add
     */
    public function set(string $id, mixed $service): void;
}
