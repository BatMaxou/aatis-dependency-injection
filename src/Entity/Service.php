<?php

namespace Aatis\DependencyInjection\Entity;

class Service
{
    /**
     * @var array<string, class-string|string|null>|null
     */
    private ?array $dependencies = null;

    private ?object $instance = null;

    /**
     * @var array<string, mixed>
     */
    private array $givenArgs = [];

    /**
     * @var mixed[]
     */
    private array $args = [];

    /**
     * @var string[]
     */
    private array $tags = [];

    /**
     * @var string[]
     */
    private array $interfaces = [];

    /**
     * @param class-string $class
     */
    public function __construct(
        private readonly string $class,
    ) {
    }

    /**
     * @return class-string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * @return array<string, class-string|string|null>
     */
    public function getDependencies(): array
    {
        if (null === $this->dependencies) {
            $this->loadDependencies();
        }

        /** @var array<string, class-string|string|null> */
        $dependencies = $this->dependencies;

        return $dependencies;
    }

    public function getInstance(): ?object
    {
        return $this->instance;
    }

    /**
     * @return array<string, mixed>
     */
    public function getGivenArgs(): array
    {
        return $this->givenArgs;
    }

    /**
     * @return array<string, mixed>
     */
    public function getArgs(): array
    {
        return $this->args;
    }

    /**
     * @return string[]
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * @return string[]
     */
    public function getInterfaces(): array
    {
        return $this->interfaces;
    }

    public function setInstance(object $instance): static
    {
        $this->instance = $instance;

        return $this;
    }

    /**
     * @param array<string, mixed> $givenArgs
     */
    public function setGivenArgs(array $givenArgs): static
    {
        $this->givenArgs = $givenArgs;

        return $this;
    }

    /**
     * @param mixed[] $args
     */
    public function setArgs(array $args): static
    {
        $this->args = $args;

        return $this;
    }

    /**
     * @param string[] $tags
     */
    public function setTags(array $tags): static
    {
        $this->tags = $tags;

        return $this;
    }

    /**
     * @param string[] $interfaces
     */
    public function setInterfaces(array $interfaces): static
    {
        $this->interfaces = $interfaces;

        return $this;
    }

    /**
     * @return array{
     *  class: class-string,
     *  givenArgs: array<string, mixed>,
     *  args: mixed[],
     *  tags: string[],
     *  interfaces: string[]
     * }
     */
    public function toArray(): array
    {
        return [
            'class' => $this->class,
            'dependencies' => $this->dependencies,
            'givenArgs' => $this->givenArgs,
            'args' => $this->args,
            'tags' => $this->tags,
            'interfaces' => $this->interfaces,
        ];
    }

    private function loadDependencies(): void
    {
        $dependencies = [];
        $reflexion = new \ReflectionClass($this->class);
        $constructor = $reflexion->getConstructor();

        if (!$constructor) {
            return;
        }

        $parameters = $constructor->getParameters();

        foreach ($parameters as $parameter) {
            $type = $parameter->getType();

            if (!$type || !($type instanceof \ReflectionNamedType)) {
                $dependencies[$parameter->getName()] = null;
            } elseif (str_contains($type->getName(), '\\')) {
                $dependencies[$parameter->getName()] = $type->getName();
            } else {
                if (str_starts_with($parameter->getName(), '_')) {
                    $dependencies['APP'.strtoupper($parameter->getName())] = null;
                } else {
                    $dependencies[$parameter->getName()] = $type->getName();
                }
            }
        }

        $this->dependencies = $dependencies;
    }
}
