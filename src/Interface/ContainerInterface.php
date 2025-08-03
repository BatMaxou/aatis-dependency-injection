<?php

namespace Aatis\DependencyInjection\Interface;

use Psr\Container\ContainerInterface as PsrContainerInterface;

interface ContainerInterface extends PsrContainerInterface
{
    public const ALL_SERVICES = '@all_services';
    public const ALL_ENV = '@all_env';

    /**
     * Add a service to the container.
     *
     * @param string $id id of the data to add
     * @param mixed $service Data to add
     */
    public function set(string $id, mixed $service): void;
}
