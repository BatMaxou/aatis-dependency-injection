<?php

namespace Aatis\DependencyInjection\Component;

use Aatis\Tag\Interface\TagInterface;

/**
 * @template T of object
 */
class Service
{
    private LazyDependencies $dependencies;

    /**
     * @var T|null
     */
    private ?object $instance = null;

    /**
     * @var ?\ReflectionClass<T>
     */
    private ?\ReflectionClass $reflexion = null;

    /**
     * @var array<string, mixed>
     */
    private array $givenArgs = [];

    /**
     * @var mixed[]
     */
    private array $args = [];

    /**
     * @var ServiceTag[]
     */
    private array $tags = [];

    /**
     * @var class-string[]
     */
    private array $abstracts = [];

    /**
     * @param class-string<T> $class
     */
    public function __construct(private readonly string $class)
    {
        $this->dependencies = new LazyDependencies();
    }

    /**
     * @return class-string<T>
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * @return Dependency[]
     */
    public function getDependencies(): array
    {
        return $this->dependencies->get($this->getReflexion());
    }

    /**
     * @return T|null
     */
    public function getInstance(): ?object
    {
        return $this->instance;
    }

    /**
     * @return \ReflectionClass<T>
     */
    public function getReflexion(): \ReflectionClass
    {
        if (null === $this->reflexion) {
            $this->reflexion = new \ReflectionClass($this->class);
        }

        return $this->reflexion;
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
     * @return ServiceTag[]
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * @return class-string[]
     */
    public function getAbstracts(): array
    {
        return $this->abstracts;
    }

    public function hasTag(string|TagInterface $tag): bool
    {
        return in_array($tag, $this->tags);
    }

    /**
     * @param T $instance
     */
    public function setInstance(object $instance): static
    {
        $this->instance = $instance;

        return $this;
    }

    /**
     * @param \ReflectionClass<T> $reflexion
     */
    public function setReflexion(\ReflectionClass $reflexion): static
    {
        $this->reflexion = $reflexion;

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
     * @param ServiceTag[] $tags
     */
    public function setTags(array $tags): static
    {
        $this->tags = $tags;

        return $this;
    }

    /**
     * @param class-string[] $abstracts
     */
    public function setAbstracts(array $abstracts): static
    {
        $this->abstracts = $abstracts;

        return $this;
    }
}
