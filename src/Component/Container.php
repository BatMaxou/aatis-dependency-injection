<?php

namespace Aatis\DependencyInjection\Component;

use Aatis\DependencyInjection\Exception\DataTypeException;
use Aatis\DependencyInjection\Exception\ServiceNotFoundException;
use Aatis\DependencyInjection\Interface\ContainerInterface;
use Aatis\DependencyInjection\Interface\ServiceFactoryInterface;
use Aatis\DependencyInjection\Service\ServiceInstanciator;
use Aatis\DependencyInjection\Service\ServiceTagBuilder;
use Aatis\DependencyInjection\Trait\ContainerTrait;

class Container implements ContainerInterface
{
    use ContainerTrait {
        get as getService;
    }

    /**
     * @var array<string, mixed>
     */
    private array $env = [];

    public function __construct(
        private readonly ServiceFactoryInterface $serviceFactory,
        ServiceTagBuilder $serviceTagBuilder,
    ) {
        $this->serviceInstanciator = new ServiceInstanciator($serviceTagBuilder);
        $this->serviceInstanciator->setContainer($this);
        $this->set(ServiceInstanciator::class, $serviceFactory->create(ServiceInstanciator::class)->setInstance($this->serviceInstanciator));
        $this->set(Container::class, $serviceFactory->create(Container::class)->setInstance($this));
    }

    public function has(string $id): bool
    {
        return isset($this->services[$id]) || isset($this->env[$id]);
    }

    public function get(string $id): mixed
    {
        if (self::ALL_ENV === $id) {
            return $this->env;
        }

        if (str_starts_with($id, '@_')) {
            return $this->env[$id] ?? null;
        }

        [$result, $id, $serviceWanted] = $this->getService($id);
        if (null !== $result) {
            return $result;
        }

        if (class_exists($id)) {
            $service = $this->serviceFactory->create($id);
            $this->set($id, $service);

            return $serviceWanted ? $service : $this->getServiceInstance($service);
        }

        throw new ServiceNotFoundException(sprintf('Service %s not found', $id));
    }

    public function set(string $id, mixed $value): void
    {
        if (str_starts_with($id, '@_')) {
            $this->env[$id] = $value;

            return;
        }

        if ($value instanceof Service) {
            $this->services[$id] = $value;

            return;
        }

        throw new DataTypeException(sprintf('Can\'t set %s, value is neither a Service nor a env variable', $id));
    }
}
