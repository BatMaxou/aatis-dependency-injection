<?php

namespace Aatis\DependencyInjection\Component;

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

    public function isNamespace(): bool
    {
        return str_contains($this->type, '\\');
    }

    public function isEnvVariable(): bool
    {
        return str_starts_with($this->name, '_');
    }
}
