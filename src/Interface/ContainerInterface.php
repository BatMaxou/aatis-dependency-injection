<?php

namespace Aatis\DependencyInjection\Interface;

use Psr\Container\ContainerInterface as PsrContainerInterface;

interface ContainerInterface extends PsrContainerInterface
{
    /**
     * Finds entries of the container by the given tag and returns them.
     *
     * @param string $tag Tag of the entries to look for
     *
     * @return mixed[] Entries
     */
    public function getByTag(string $tag, bool $serviceWanted = false): array;

    /**
     * Finds entries of the container by the given tags and returns them.
     *
     * @param string[] $tags Tags of the entries to look for
     *
     * @return mixed[] Entries
     */
    public function getByTags(array $tags, bool $serviceWanted = false): array;

    /**
     * Finds an entry of the container by the given interface and returns them.
     *
     * @param string $interface Interface of the entry to look for
     *
     * @return mixed[] Entries
     */
    public function getByInterface(string $interface, bool $serviceWanted = false): array;

    /**
     * Finds entries of the container by the given interfaces and returns them.
     *
     * @param string[] $interfaces Interfaces of the entries to look for
     *
     * @return mixed[] Entries
     */
    public function getByInterfaces(array $interfaces, bool $serviceWanted = false): array;

    /**
     * Add a service to the container.
     *
     * @param string $id id of the data to add
     * @param mixed $service Data to add
     */
    public function set(string $id, mixed $service): void;
}
