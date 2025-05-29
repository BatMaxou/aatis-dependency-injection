<?php

namespace Aatis\DependencyInjection\Component;

use Aatis\DependencyInjection\Exception\DataTypeException;
use Aatis\DependencyInjection\Exception\ServiceNotFoundException;
use Aatis\DependencyInjection\Interface\ContainerInterface;
use Aatis\DependencyInjection\Interface\ServiceFactoryInterface;
use Aatis\DependencyInjection\Service\ServiceInstanciator;
use Aatis\DependencyInjection\Service\ServiceTagBuilder;

class Container implements ContainerInterface
{
    /**
     * @var array<string, mixed>
     */
    private array $env = [];

    /**
     * @var array<string, Service<object>>
     */
    private array $services = [];

    private readonly ServiceInstanciator $serviceInstanciator;

    public function __construct(
        private readonly ServiceFactoryInterface $serviceFactory,
        ServiceTagBuilder $serviceTagBuilder,
    ) {
        $this->serviceInstanciator = new ServiceInstanciator($serviceTagBuilder);
        $this->serviceInstanciator->setContainer($this);
        $this->set(ServiceInstanciator::class, $serviceFactory->create(ServiceInstanciator::class)->setInstance($this->serviceInstanciator));
        $this->set(Container::class, $serviceFactory->create(Container::class)->setInstance($this));
    }

    public function get(string $id): mixed
    {
        if (str_starts_with($id, '@_')) {
            return $this->env[$id] ?? null;
        }

        $serviceWanted = false;
        if (str_starts_with($id, ServiceTag::SERVICE_TARGETED_PREFIX)) {
            $id = str_replace(ServiceTag::SERVICE_TARGETED_PREFIX, '', $id);
            $serviceWanted = true;
        }

        if (str_starts_with($id, ServiceTag::FROM_CLASS_PREFIX)) {
            $id = str_replace([ServiceTag::FROM_CLASS_PREFIX, ServiceTag::TAG_PREFIX], '', $id);
        }

        if (str_starts_with($id, ServiceTag::TAG_PREFIX)) {
            $serviceMapping = [];
            foreach ($this->services as $service) {
                $tags = $service->getTags();

                $foundedTag = array_find($tags, fn ($tag) => $tag->getName() === $id);
                if (!$foundedTag) {
                    continue;
                }

                $serviceMapping[$this->getTagPriority($foundedTag)][] = $serviceWanted ? $service : $this->getServiceInstance($service);
            }

            $nbServices = count($serviceMapping);
            if ($nbServices > 1) {
                krsort($serviceMapping);

                return array_merge(...array_values($serviceMapping));
            }

            return match ($nbServices) {
                1 => array_values($serviceMapping)[0],
                default => [],
            };
        }

        if (isset($this->services[$id])) {
            $service = $this->services[$id];

            return $serviceWanted ? $service : $this->getServiceInstance($service);
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

    /**
     * @param class-string $class
     */
    public function has(string $class): bool
    {
        return isset($this->services[$class]);
    }

    /**
     * @param Service<object> $service
     */
    private function getServiceInstance(Service $service): object
    {
        $instance = $service->getInstance();
        if ($instance) {
            return $instance;
        }

        return $this->serviceInstanciator->instanciate($service);
    }

    private function getTagPriority(ServiceTag $tag): int
    {
        $priority = 0;
        if ($tag->getParameters()->has('priority')) {
            $priority = $tag->getParameters()->get('priority');
        }

        if (!is_int($priority)) {
            throw new DataTypeException('Priority must be an integer');
        }

        return $priority;
    }
}
