<?php

namespace Aatis\DependencyInjection\Component;

use Aatis\DependencyInjection\Interface\ContainerInterface;
use Psr\Container\ContainerInterface as PsrContainerInterface;

class Dependency
{
    public string $name;

    public string $type;

    public bool $nullable;

    public mixed $default;

    public function __construct(string $name, string $type, bool $nullable, mixed $default)
    {
        $this->name = $name;
        $this->type = $type;
        $this->nullable = $nullable;
        $this->default = $default;
    }

    public function isContainerInterface(): bool
    {
        return ContainerInterface::class === $this->type || PsrContainerInterface::class === $this->type;
    }

    public function isServiceStack(): bool
    {
        return ServiceStack::class === $this->type;
    }

    public function isNamespace(): bool
    {
        return str_contains($this->type, '\\');
    }

    public function isEnvVariable(): bool
    {
        return str_starts_with($this->name, '_');
    }
}
