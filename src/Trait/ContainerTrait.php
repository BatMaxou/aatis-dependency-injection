<?php

namespace Aatis\DependencyInjection\Trait;

use Aatis\DependencyInjection\Component\Service;
use Aatis\DependencyInjection\Component\ServiceTag;
use Aatis\DependencyInjection\Exception\DataTypeException;
use Aatis\DependencyInjection\Interface\ContainerInterface;
use Aatis\DependencyInjection\Interface\ServiceInstanciatorInterface;

trait ContainerTrait
{
    /**
     * @var array<string, Service<object>>
     */
    private array $services = [];

    private readonly ServiceInstanciatorInterface $serviceInstanciator;

    /**
     * @return array{
     *    0: mixed,
     *    1: string,
     *    2: bool
     * }
     */
    public function get(string $id): array
    {
        if (ContainerInterface::ALL_SERVICES === $id) {
            return [$this->services, $id, true];
        }

        $serviceWanted = str_starts_with($id, ServiceTag::SERVICE_TARGETED_PREFIX);
        if ($serviceWanted) {
            $id = str_replace(ServiceTag::SERVICE_TARGETED_PREFIX, '', $id);
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

                $serviceMapping[$this->getTagPriority($foundedTag)][] = $this->formatService($serviceWanted, $service);
            }

            $nbServices = count($serviceMapping);
            if ($nbServices > 1) {
                krsort($serviceMapping);

                return [array_merge(...array_values($serviceMapping)), $id, $serviceWanted];
            }

            return match ($nbServices) {
                1 => [array_values($serviceMapping)[0], $id, $serviceWanted],
                default => [null, $id, $serviceWanted],
            };
        }

        if (isset($this->services[$id])) {
            $service = $this->services[$id];

            return [$this->formatService($serviceWanted, $service), $id, $serviceWanted];
        }

        return [null, $id, $serviceWanted];
    }

    /**
     * @param Service<object> $service
     */
    private function formatService(bool $serviceWanted, Service $service): mixed
    {
        return $serviceWanted ? $service : $this->getServiceInstance($service);
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
