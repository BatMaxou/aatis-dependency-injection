<?php

namespace Aatis\DependencyInjection\Service;

use Aatis\DependencyInjection\Attribute\AsDefaultTaggedService;
use Aatis\DependencyInjection\Component\Service;
use Aatis\DependencyInjection\Component\ServiceTag;
use Aatis\DependencyInjection\Interface\ServiceFactoryInterface;
use Aatis\ParameterBag;
use Aatis\Tag\Enum\TagOption;

class ServiceFactory implements ServiceFactoryInterface
{
    /**
     * @param array<string, array{
     *  environment?: array<string>,
     *  arguments?: array<mixed>,
     *  tags?: array<string|array<string, string>>
     * }> $givenParams
     */
    public function __construct(
        private readonly ServiceTagBuilder $serviceTagBuilder,
        private readonly array $givenParams,
    ) {
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $namespace
     *
     * @return Service<T>
     */
    public function create(string $namespace): Service
    {
        $service = new Service($namespace);
        $priorisationTags = $this->getPriotisationTags($service);

        $tags = [];
        $interfaces = array_values(class_implements($namespace));
        /** @var ServiceTag $tag */
        foreach ($this->serviceTagBuilder->buildFromInterfaces($interfaces, [TagOption::BUILD_OBJECT]) as $tag) {
            $tags[] = $tag->setParameters($this->buildParameters(['tag' => $tag->getName()], $priorisationTags));
        }

        if (isset($this->givenParams[$namespace])) {
            if (isset($this->givenParams[$namespace]['tags'])) {
                $tagParams = $this->givenParams[$namespace]['tags'];
                if (is_array($tagParams)) {
                    foreach ($tagParams as $tagParameter) {
                        if (is_string($tagParameter)) {
                            /** @var ServiceTag */
                            $tag = $this->serviceTagBuilder->buildFromName($tagParameter, [TagOption::BUILD_OBJECT]);
                            $tags[] = $tag->setParameters($this->buildParameters(['tag' => $tag->getName()], $priorisationTags));
                        }

                        if (is_array($tagParameter) && isset($tagParameter['tag'])) {
                            /** @var ServiceTag */
                            $tag = $this->serviceTagBuilder->buildFromName($tagParameter['tag'], [TagOption::BUILD_OBJECT]);
                            $tags[] = $tag->setParameters($this->buildParameters($tagParameter, $priorisationTags));
                        }
                    }
                }
            }

            if (isset($this->givenParams[$namespace]['arguments'])) {
                $service->setGivenArgs($this->givenParams[$namespace]['arguments']);
            }
        }

        if (!empty($tags)) {
            /** @var ServiceTag[] $tags */
            $service->setTags(array_unique(array_reverse($tags)));
        }

        return $service;
    }

    /**
     * @param array{
     *  tag: string,
     * } $tagParameter
     * @param array<string>|null $priorisationTags
     */
    private function buildParameters(array $tagParameter, ?array $priorisationTags): ParameterBag
    {
        $tag = $tagParameter['tag'];
        unset($tagParameter['tag']);

        $bag = new ParameterBag($tagParameter);
        if (
            null !== $priorisationTags
            && !$bag->has('priority')
            && (
                empty($priorisationTags)
                || in_array($tag, $priorisationTags)
            )
        ) {
            $bag->set('priority', 1);
        }

        return $bag;
    }

    /**
     * @param Service<object> $service
     *
     * @return array<string>|null
     */
    private function getPriotisationTags(Service $service): ?array
    {
        $attributes = $service->getReflexion()->getAttributes(AsDefaultTaggedService::class);

        return empty($attributes) ? null : $this->transformTags($attributes[0]->newInstance()->getTags());
    }

    /**
     * @param array<string> $tags
     *
     * @return array<string>
     */
    private function transformTags(array $tags): array
    {
        return array_map(fn (string $tagName) => $this->serviceTagBuilder->buildFromName($tagName), $tags);
    }
}
