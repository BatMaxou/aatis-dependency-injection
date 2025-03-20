<?php

namespace Aatis\DependencyInjection\Component;

use Aatis\DependencyInjection\Exception\DataTypeException;
use Aatis\DependencyInjection\Exception\ServiceNotFoundException;
use Aatis\DependencyInjection\Interface\ContainerInterface;
use Aatis\DependencyInjection\Interface\ServiceInstanciatorInterface;

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

    private ServiceInstanciatorInterface $serviceInstanciator;

    public function __construct(ServiceInstanciatorInterface $serviceInstanciator)
    {
        $serviceInstanciator->setContainer($this);
        $this->serviceInstanciator = $serviceInstanciator;
    }

    public function get(string $id): mixed
    {
        if (str_starts_with($id, '@_')) {
            return $this->env[$id] ?? null;
        }

        $serviceWanted = false;
        if (str_starts_with($id, ServiceTag::SERVICE_TARGETED_PREFIX)) {
            $id = str_replace(ServiceTag::SERVICE_TARGETED_PREFIX, ServiceTag::TAG_PREFIX, $id);
            $serviceWanted = true;
        }

        if (str_starts_with($id, ServiceTag::TAG_PREFIX)) {
            $serviceMapping = [];
            foreach ($this->services as $service) {
                $tags = $service->getTags();

                $foundedTag = array_find($tags, fn ($tag) => $tag->getName() === $id);
                if (!$foundedTag) {
                    continue;
                }

                $priority = 0;
                if ($foundedTag->getParameters()->has('priority')) {
                    $priority = $foundedTag->getParameters()->get('priority');
                }

                $serviceMapping[$priority][] = $serviceWanted ? $service : $this->getServiceInstance($service);
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

            return $this->getServiceInstance($service);
        }

        throw new ServiceNotFoundException(sprintf('Service %s not found', $id));
    }

    public function set(string $id, mixed $value): void
    {
        if (
            'string' === gettype($value)
            && str_starts_with($id, '@_')
        ) {
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
        return $service->getInstance() ?? $this->serviceInstanciator->instanciate($service);
    }
}
