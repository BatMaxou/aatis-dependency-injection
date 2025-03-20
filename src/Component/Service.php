<?php

namespace Aatis\DependencyInjection\Component;

/**
 * @template T of object
 *
 * @phpstan-type ServiceDependencies array<string, array{
 *  type: class-string|string|null,
 *  nullable: bool,
 *  default: mixed
 * }>
 */
class Service
{
    /**
     * @var ServiceDependencies|null
     */
    private ?array $dependencies = null;

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
     * @param class-string<T> $class
     */
    public function __construct(
        private readonly string $class,
    ) {
    }

    /**
     * @return class-string<T>
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * @return ServiceDependencies
     */
    public function getDependencies(): array
    {
        if (null === $this->dependencies) {
            $this->loadDependencies();
        }

        /** @var ServiceDependencies */
        $dependencies = $this->dependencies;

        return $dependencies;
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
     * @return array{
     *  class: class-string<T>,
     *  dependencies: ServiceDependencies|null,
     *  givenArgs: array<string, mixed>,
     *  args: mixed[],
     *  tags: ServiceTag[],
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
        ];
    }

    private function loadDependencies(): void
    {
        $dependencies = [];
        $reflexion = $this->getReflexion();
        $constructor = $reflexion->getConstructor();

        if ($constructor) {
            $parameters = $constructor->getParameters();

            foreach ($parameters as $parameter) {
                $type = $parameter->getType();

                if (!$type || !($type instanceof \ReflectionNamedType)) {
                    throw new \LogicException('Type don\'t have a name');
                }

                $infos = [
                    'type' => $type->getName(),
                    'nullable' => $parameter->allowsNull(),
                    'default' => $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null,
                ];

                if (str_contains($type->getName(), '\\')) {
                    $dependencies[$parameter->getName()] = $infos;
                } else {
                    if (str_starts_with($parameter->getName(), '_')) {
                        $dependencies[sprintf('@%s', strtoupper($parameter->getName()))] = $infos;
                    } else {
                        $dependencies[$parameter->getName()] = $infos;
                    }
                }
            }
        }

        $this->dependencies = $dependencies;
    }
}
