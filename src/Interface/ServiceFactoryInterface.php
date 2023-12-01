<?php

namespace Aatis\DependencyInjection\Interface;

use Aatis\DependencyInjection\Entity\Service;

interface ServiceFactoryInterface
{
    public function create(string $namespace): Service;
}
