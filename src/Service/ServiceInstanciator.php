<?php

namespace Aatis\DependencyInjection\Service;

use Aatis\DependencyInjection\Component\Dependency;
use Aatis\DependencyInjection\Component\Service;
use Aatis\DependencyInjection\Component\ServiceStack;
use Aatis\DependencyInjection\Enum\ServiceTagOption;
use Aatis\DependencyInjection\Exception\ArgumentNotFoundException;
use Aatis\DependencyInjection\Exception\ClassNotFoundException;
use Aatis\DependencyInjection\Exception\EnvironmentVariableNotFoundException;
use Aatis\DependencyInjection\Exception\MissingContainerException;
use Aatis\DependencyInjection\Exception\ServiceNotFoundException;
use Aatis\DependencyInjection\Interface\ServiceInstanciatorInterface;
use Aatis\DependencyInjection\Interface\ServiceSubscriberInterface;
use Psr\Container\ContainerInterface;

class ServiceInstanciator implements ServiceInstanciatorInterface
{
    private ?ContainerInterface $container = null;

    public function __construct(private readonly ServiceTagBuilder $serviceTagBuilder)
    {
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

        $service->setArgs($this->loadArgs($service));
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
        $args = [];
        $givenArgs = $service->getGivenArgs();
        $isServiceSubscriber = $service->hasTag($this->serviceTagBuilder->buildFromInterface(ServiceSubscriberInterface::class));

        foreach ($service->getDependencies() as $dependency) {
            if ($isServiceSubscriber && ($dependency->isContainerInterface() || $dependency->isServiceStack())) {
                /** @var Service<ServiceSubscriberInterface> $service */
                $args[] = $this->loadServiceStack($dependency, $service);
                continue;
            }

            $args[] = match (true) {
                $dependency->isNamespace() => $this->loadServiceDependency($dependency, $givenArgs),
                $dependency->isEnvVariable() => $this->loadEnvVariable($dependency),
                default => $this->loadArgument($dependency, $givenArgs, $service->getClass()),
            };
        }

        return $args;
    }

    /**
     * @param array<string, mixed> $givenArgs
     * @param class-string $serviceClass
     */
    private function loadArgument(Dependency $dependency, array $givenArgs, string $serviceClass): mixed
    {
        if (isset($givenArgs[$dependency->name])) {
            return $givenArgs[$dependency->name];
        }

        if (null === $dependency->default && !$dependency->nullable) {
            throw new ArgumentNotFoundException(sprintf('Missing argument %s for %s class', $dependency->name, $serviceClass));
        }

        return $dependency->default;
    }

    private function loadEnvVariable(Dependency $dependency): mixed
    {
        $value = $this->container?->get(sprintf('@%s', strtoupper($dependency->name))) ?? $dependency->default;
        if (null === $value && !$dependency->nullable) {
            throw new EnvironmentVariableNotFoundException(sprintf('Missing environment variable %s', $dependency->name));
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $givenArgs
     */
    private function loadServiceDependency(Dependency $dependency, array $givenArgs): mixed
    {
        if (interface_exists($dependency->type)) {
            return $this->guessServiceFromInterface($dependency->type, $givenArgs, $dependency->name);
        }

        try {
            return $this->container?->get($dependency->type) ?? throw new MissingContainerException('Container not set');
        } catch (ServiceNotFoundException $e) {
            if (null === $dependency->default && !$dependency->nullable) {
                throw $e;
            }

            return $dependency->default;
        }
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

        if (isset($givenArgs[$varName]) && is_string($givenArgs[$varName])) {
            /** @var object $serviceInstance */
            $serviceInstance = $container->get($givenArgs[$varName]);
            if (!$serviceInstance instanceof $interfaceNamespace) {
                throw new \LogicException(sprintf('Container does not return the wanted object, %s return', get_debug_type($serviceInstance)));
            }

            return $serviceInstance;
        }

        /** @var Service<object>[] $services */
        $services = $container->get($this->serviceTagBuilder->buildFromInterface($interfaceNamespace, [ServiceTagOption::SERVICE_TARGETED]));
        if (empty($services)) {
            throw new ClassNotFoundException(sprintf('Missing class implementing %s interface', $interfaceNamespace));
        }

        return $services[0]->getInstance() ?? $this->instanciate($services[0]);
    }

    /**
     * @param Service<ServiceSubscriberInterface> $service
     */
    private function loadServiceStack(Dependency $dependency, Service $service): mixed
    {
        if (null === $container = $this->container) {
            throw new MissingContainerException('Container not set');
        }

        $services = [];
        $toPick = $service->getClass()::getSubscribedServices($this->serviceTagBuilder);
        foreach ($toPick as $tag) {
            try {
                $result = $container->get($tag);
            } catch (ServiceNotFoundException $e) {
                $result = [];
            }

            /** @var Service<object>[]|Service<object> $result */
            if (is_array($result)) {
                foreach ($result as $service) {
                    $this->pushServiceToStack($service, $services);
                }

                continue;
            }

            $this->pushServiceToStack($result, $services);
        }

        if (empty($services)) {
            if (null === $dependency->default && $dependency->nullable) {
                return null;
            }

            if ($dependency->default) {
                return $dependency->default;
            }
        }

        return new ServiceStack($this, $services);
    }

    /**
     * @param Service<object> $service
     * @param array<Service<object>> $services
     */
    private function pushServiceToStack(Service $service, array &$services): void
    {
        $services[$service->getClass()] = $service;
    }
}
