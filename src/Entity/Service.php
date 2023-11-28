<?php

namespace Aatis\DependencyInjection\Entity;

use Aatis\DependencyInjection\Exception\ArgumentNotFoundException;
use Aatis\DependencyInjection\Exception\ClassNotFoundException;
use Aatis\DependencyInjection\Exception\MissingContainerException;
use Aatis\DependencyInjection\Interface\ContainerInterface;

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

    /**
     * @var string[]
     */
    private array $interfaces = [];

    private static ?ContainerInterface $container = null;

    /**
     * @var \ReflectionClass<ContainerInterface>|null
     */
    private static ?\ReflectionClass $containerReflection = null;

    /**
     * @param class-string $class
     */
    public function __construct(
        private readonly string $class,
    ) {
    }

    public static function setContainer(ContainerInterface $container): void
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

    /**
     * @return string[]
     */
    public function getInterfaces(): array
    {
        return $this->interfaces;
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

    /**
     * @param string[] $interfaces
     */
    public function setInterfaces(array $interfaces): static
    {
        $this->interfaces = $interfaces;

        return $this;
    }

    public function setInstance(object $instance): static
    {
        $this->instance = $instance;

        return $this;
    }

    public function isInstancied(): bool
    {
        return $this->instance ? true : false;
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
            'givenArgs' => $this->givenArgs,
            'args' => $this->args,
            'tags' => $this->tags,
            'interfaces' => $this->interfaces,
        ];
    }

    private function instanciate(): void
    {
        if (!empty($args = $this->loadArgs())) {
            $this->args = $args;
        }

        $this->instance = new ($this->class)(...$this->args);
    }

    /**
     * @return mixed[]
     */
    private function loadArgs(): array
    {
        if (!self::$container) {
            throw new MissingContainerException('Container not set');
        }

        $args = [];

        foreach ($this->getDependencies() as $varName => $dependencyType) {
            if (self::$container::class === $dependencyType) {
                $args[] = self::$container;
            } elseif ($dependencyType && str_contains($dependencyType, '\\')) {
                /** @var class-string $dependencyType */
                if (interface_exists($dependencyType)) {
                    $args[] = $this->pickServiceWithInterface($varName, $dependencyType);
                } else {
                    if (!self::$container->has($dependencyType)) {
                        if (class_exists($dependencyType)) {
                            $this->createDependencyService($dependencyType);
                        } else {
                            throw new ClassNotFoundException(sprintf('Class %s not found', $dependencyType));
                        }
                    }

                    $args[] = self::$container->get($dependencyType);
                }
            } else {
                if (!isset($this->givenArgs[$varName])) {
                    throw new ArgumentNotFoundException(sprintf('Missing argument %s for %s class', $varName, $this->class));
                }

                $args[] = $this->givenArgs[$varName];
            }
        }

        return $args;
    }

    private function pickServiceWithInterface(string $varName, string $interfaceNamespace): object
    {
        if (!self::$container) {
            throw new MissingContainerException('Container not set');
        }

        if (!self::$containerReflection) {
            self::$containerReflection = new \ReflectionClass(self::$container);
        }

        if (self::$containerReflection->implementsInterface($interfaceNamespace)) {
            return self::$container;
        }

        if (isset($this->givenArgs[$varName])) {
            /** @var class-string $implementingClass */
            $implementingClass = $this->givenArgs[$varName];
            if (class_exists($implementingClass)) {
                if (!self::$container->has($implementingClass)) {
                    $this->createDependencyService($implementingClass);
                }

                $service = self::$container->get($implementingClass);

                if (!$service || !$service instanceof $interfaceNamespace) {
                    throw new \LogicException(sprintf('Container does not return the wanted object, %s return', get_debug_type($service)));
                }

                return $service;
            } else {
                throw new ClassNotFoundException(sprintf('Class %s not found', $implementingClass));
            }
        } else {
            $services = self::$container->getByInterface($interfaceNamespace);
            if (empty($services)) {
                throw new ClassNotFoundException(sprintf('Missing class implementing %s interface', $interfaceNamespace));
            }

            return $services[0]->getInstance();
        }
    }

    /**
     * @param class-string $namespace
     */
    private function createDependencyService(string $namespace): void
    {
        if (!self::$container) {
            throw new MissingContainerException('Container not set');
        }

        $service = new Service($namespace);
        self::$container->set($namespace, $service);
        $service->instanciate();
    }
}
