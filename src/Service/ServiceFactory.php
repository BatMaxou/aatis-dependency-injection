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
        $service->setAbstracts($this->getAbstracts($service));

        $priorisationTags = $this->getPriotisationTags($service);
        $tags = [
            ...$this->getInterfaceTags($service, $priorisationTags),
            ...$this->processGivenParams($service, $priorisationTags),
        ];

        if (!empty($tags)) {
            $service->setTags(array_unique(array_reverse($tags)));
        }

        return $service;
    }

    /**
     * @param Service<object> $service
     *
     * @return class-string[]
     */
    private function getAbstracts(Service $service): array
    {
        $abstracts = [];

        $rootReflection = $service->getReflexion();
        while ($parent = $rootReflection->getParentClass()) {
            if ($parent->isAbstract()) {
                $abstracts[] = $parent->getName();
            }
            $rootReflection = $parent;
        }

        return $abstracts;
    }

    /**
     * @param Service<object> $service
     *
     * @return array<string>|null
     */
    private function getPriotisationTags(Service $service): ?array
    {
        $attributes = $service->getReflexion()->getAttributes(AsDefaultTaggedService::class);

        return empty($attributes) ? null : $this->toTags($attributes[0]->newInstance()->getSubjects());
    }

    /**
     * @param string[] $subjects
     *
     * @return array<string>
     */
    private function toTags(array $subjects): array
    {
        return array_map(
            fn (string $tag) => match (true) {
                interface_exists($tag) => $this->serviceTagBuilder->buildFromInterface($tag, [TagOption::BUILD_OBJECT]),
                default => $this->serviceTagBuilder->buildFromName($tag, [TagOption::BUILD_OBJECT]),
            },
            $subjects,
        );
    }

    /**
     * @param Service<object> $service
     * @param array<string>|null $priorisationTags
     *
     * @return ServiceTag[]
     */
    private function getInterfaceTags(Service $service, ?array $priorisationTags): array
    {
        $tags = [];
        $interfaces = array_values(class_implements($service->getClass()));
        /** @var ServiceTag $tag */
        foreach ($this->serviceTagBuilder->buildFromInterfaces($interfaces, [TagOption::BUILD_OBJECT]) as $tag) {
            $tags[] = $tag->setParameters($this->buildParameters(['tag' => $tag->getName()], $priorisationTags));
        }

        return $tags;
    }

    /**
     * @param Service<object> $service
     * @param array<string>|null $priorisationTags
     *
     * @return ServiceTag[]
     */
    private function processGivenParams(Service $service, ?array $priorisationTags): array
    {
        $namespaces = $this->getNamespacesWithGivenParams($service);
        if (empty($namespaces)) {
            return [];
        }

        $service->setGivenArgs($this->getGivenArgs($namespaces));

        return $this->getGivenTags($namespaces, $priorisationTags);
    }

    /**
     * @param Service<object> $service
     *
     * @return class-string[]
     */
    private function getNamespacesWithGivenParams(Service $service): array
    {
        $namespaces = [];
        foreach ([$service->getClass(), ...$service->getAbstracts()] as $namespace) {
            if (isset($this->givenParams[$namespace])) {
                $namespaces[] = $namespace;
            }
        }

        return array_reverse($namespaces);
    }

    /**
     * @param class-string[] $targets
     *
     * @return mixed[]
     */
    private function getGivenArgs(array $targets): array
    {
        $givenArgs = [];
        foreach ($targets as $namespace) {
            if (isset($this->givenParams[$namespace]['arguments'])) {
                $givenArgs = array_merge($givenArgs, $this->givenParams[$namespace]['arguments']);
            }
        }

        return $givenArgs;
    }

    /**
     * @param class-string[] $targets
     * @param array<string>|null $priorisationTags
     *
     * @return ServiceTag[]
     */
    private function getGivenTags(array $targets, ?array $priorisationTags): array
    {
        $tags = [];
        foreach ($targets as $namespace) {
            if (!isset($this->givenParams[$namespace]['tags'])) {
                return [];
            }

            $tagParams = $this->givenParams[$namespace]['tags'];
            if (!is_array($tagParams)) {
                return [];
            }

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

        return $tags;
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
}
