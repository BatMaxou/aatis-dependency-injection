<?php

namespace Aatis\DependencyInjection\Service;

use Aatis\DependencyInjection\Component\Service;
use Aatis\DependencyInjection\Enum\ServiceTagOption;
use Aatis\DependencyInjection\Exception\ArgumentNotFoundException;
use Aatis\DependencyInjection\Exception\ClassNotFoundException;
use Aatis\DependencyInjection\Exception\MissingContainerException;
use Aatis\DependencyInjection\Interface\ContainerInterface;
use Aatis\DependencyInjection\Interface\ServiceFactoryInterface;
use Aatis\DependencyInjection\Interface\ServiceInstanciatorInterface;

class ServiceInstanciator implements ServiceInstanciatorInterface
{
    private ?ContainerInterface $container = null;

    public function __construct(
        private readonly ServiceFactoryInterface $serviceFactory,
        private readonly ServiceTagBuilder $serviceTagBuilder,
    ) {
    }

    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }

    /**
     * @template T of object
     *
     * @param Service<T> $service
     *
     * @return T
     */
    public function instanciate(Service $service): object
    {
        $instance = $service->getInstance();

        if ($instance) {
            return $instance;
        }

        if (!empty($args = $this->loadArgs($service))) {
            $service->setArgs($args);
        }

        $service->setInstance(new ($service->getClass())(...$service->getArgs()));

        return $service->getInstance() ?? throw new \LogicException('Service instance not set');
    }

    /**
     * @param Service<object> $service
     *
     * @return mixed[]
     */
    private function loadArgs(Service $service): array
    {
        $container = $this->container;

        if (!$container) {
            throw new MissingContainerException('Container not set');
        }

        $args = [];
        $givenArgs = $service->getGivenArgs();

        foreach ($service->getDependencies() as $varName => $varInfos) {
            $dependencyType = $varInfos['type'];
            $isNullable = $varInfos['nullable'];
            $defaultValue = $varInfos['default'];

            if ($container::class === $dependencyType) {
                $args[] = $container;
            } elseif ($dependencyType && str_contains($dependencyType, '\\')) {
                /** @var class-string $dependencyType */
                if (interface_exists($dependencyType)) {
                    $args[] = $this->guessServiceFromInterface($dependencyType, $givenArgs, $varName);
                } else {
                    if (!$container->has($dependencyType)) {
                        if (class_exists($dependencyType)) {
                            $this->createService($dependencyType);
                        } else {
                            throw new ClassNotFoundException(sprintf('Class %s not found', $dependencyType));
                        }
                    }

                    $args[] = $container->get($dependencyType);
                }
            } elseif (str_starts_with($varName, '@_')) {
                $args[] = $container->get($varName) ?? $defaultValue;
            } else {
                if (!isset($givenArgs[$varName])) {
                    if (!$isNullable && null === $defaultValue) {
                        throw new ArgumentNotFoundException(sprintf('Missing argument %s for %s class', $varName, $service->getClass()));
                    }

                    $args[] = $defaultValue;
                } else {
                    $args[] = $givenArgs[$varName];
                }
            }
        }

        return $args;
    }

    /**
     * @param class-string $interfaceNamespace
     * @param array<string, mixed> $givenArgs
     */
    private function guessServiceFromInterface(string $interfaceNamespace, array $givenArgs, string $varName): object
    {
        $container = $this->container;

        if (!$container) {
            throw new MissingContainerException('Container not set');
        }

        if (isset($givenArgs[$varName])) {
            /** @var class-string $implementingClass */
            $implementingClass = $givenArgs[$varName];
            if (class_exists($implementingClass)) {
                if (!$container->has($implementingClass)) {
                    $this->createService($implementingClass);
                }

                $service = $container->get($implementingClass);

                if (!$service || !$service instanceof $interfaceNamespace) {
                    throw new \LogicException(sprintf('Container does not return the wanted object, %s return', get_debug_type($service)));
                }

                return $service;
            } else {
                throw new ClassNotFoundException(sprintf('Class %s not found', $implementingClass));
            }
        } else {
            /** @var Service<object>[] $services */
            $services = $container->get($this->serviceTagBuilder->buildFromInterface($interfaceNamespace, [ServiceTagOption::SERVICE_TARGETED]));
            if (empty($services)) {
                throw new ClassNotFoundException(sprintf('Missing class implementing %s interface', $interfaceNamespace));
            } elseif (1 === count($services)) {
                return $services[0]->getInstance() ?? $this->instanciate($services[0]);
            } else {
                $i = 0;
                $choosenService = null;

                while ($i < count($services) && !$choosenService) {
                    if ($instance = $services[$i]->getInstance()) {
                        $choosenService = $instance;
                    }
                    ++$i;
                }

                return $choosenService ?? $this->instanciate($services[0]);
            }
        }
    }

    /**
     * @param class-string $namespace
     */
    private function createService(string $namespace): void
    {
        if (!$this->container) {
            throw new MissingContainerException('Container not set');
        }

        $service = $this->serviceFactory->create($namespace);
        $this->container->set($namespace, $service);
        $this->instanciate($service);
    }
}
