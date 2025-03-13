<?php

namespace Aatis\DependencyInjection\Interface;

use Aatis\DependencyInjection\Component\Service;

interface ServiceFactoryInterface
{
    public function create(string $namespace): Service;
}
