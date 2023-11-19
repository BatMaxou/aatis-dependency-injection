<?php

namespace Aatis\DependencyInjection\Entity;

class Service
{
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

    private static ?Container $container = null;

    /**
     * @param class-string $class
     */
    public function __construct(
        private string $class,
    ) {
        $this->class = $class;
    }

    public static function setContainer(Container $container): void
    {
        self::$container = $container;
    }

    /**
     * @return class-string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * @return string[]
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    public function getInstance(): object
    {
        if (self::$container && $this->class === self::$container::class) {
            return self::$container;
        }

        if (!$this->instance) {
            $this->instanciate();
        }

        /**
         * @var object $instance
         */
        $instance = $this->instance;

        return $instance;
    }

    /**
     * @return array<string, class-string|string|null>
     */
    public function getDependencies(): array
    {
        $dependencies = [];
        $reflexion = new \ReflectionClass($this->class);
        $constructor = $reflexion->getConstructor();

        if (!$constructor) {
            return $dependencies;
        }

        $parameters = $constructor->getParameters();

        foreach ($parameters as $parameter) {
            $type = $parameter->getType();

            if (!$type || !($type instanceof \ReflectionNamedType)) {
                $dependencies[$parameter->getName()] = null;
            } elseif (str_contains($type->getName(), '\\')) {
                $dependencies[$parameter->getName()] = $type->getName();
            } else {
                $dependencies[$parameter->getName()] = $type->getName();
            }
        }

        return $dependencies;
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

    public function setInstance(object $instance): static
    {
        $this->instance = $instance;

        return $this;
    }

    private function instanciate(): void
    {
        if (!self::$container) {
            throw new \Exception('Container not set');
        }

        $args = [];

        foreach ($this->getDependencies() as $varName => $dependencyType) {
            if (self::$container::class === $dependencyType) {
                $args[] = self::$container;
            } elseif ($dependencyType && str_contains($dependencyType, '\\')) {
                /** @var class-string $dependencyType */
                if (!self::$container->has($dependencyType)) {
                    if (class_exists($dependencyType)) {
                        $service = new Service($dependencyType);
                        self::$container->set($dependencyType, $service);
                        $service->instanciate();
                    } else {
                        throw new \Exception("Class $dependencyType not found");
                    }
                }
                $args[] = self::$container->get($dependencyType);
            } else {
                $args[] = $this->givenArgs[$varName];
            }
        }

        if (!empty($args)) {
            $this->setArgs($args);
        }

        $this->instance = new ($this->class)(...$this->args);
    }

    public function isInstancied(): bool
    {
        return $this->instance ? true : false;
    }

    /**
     * @return array{
     *  class: class-string,
     *  args: mixed[]
     * }
     */
    public function toArray(): array
    {
        return [
            'class' => $this->class,
            'args' => $this->args,
        ];
    }
}
