<?php

namespace Aatis\DependencyInjection\Trait;

use Aatis\DependencyInjection\Interface\ContainerInterface;
use Psr\Container\ContainerInterface as PsrContainerInterface;

/**
 * @template Input
 * @template Output
 * @template Context of array
 */
trait ServiceSubscriberTrait
{
    public function __construct(
        protected readonly PsrContainerInterface $serviceStack,
    ) {
    }

    /**
     * @param Context $ctx
     *
     * @return Output[]
     */
    public function provide(array $ctx): array
    {
        $results = [];

        /** @var Input[] $services */
        $services = $this->serviceStack->get(ContainerInterface::ALL_SERVICES);
        foreach ($services as $service) {
            if ($this->pick($service, $ctx)) {
                $results[] = $this->transformOut($service, $ctx);
            }
        }

        return $results;
    }

    /**
     * @param Input $service
     * @param Context $ctx
     *
     * @return Output
     */
    protected function transformOut(mixed $service, array $ctx): mixed
    {
        /** @var Output $service */
        return $service;
    }

    /**
     * @param Input $service
     * @param Context $ctx
     */
    protected function pick(mixed $service, array $ctx): bool
    {
        return true;
    }
}
