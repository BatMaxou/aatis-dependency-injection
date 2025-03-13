<?php

namespace Aatis\DependencyInjection\Service;

use Aatis\DependencyInjection\Component\Service;
use Aatis\DependencyInjection\Interface\ServiceFactoryInterface;

class ServiceFactory implements ServiceFactoryInterface
{
    /**
     * @param array<string, array{
     *  environment?: array<string>,
     *  arguments?: array<mixed>,
     *  tags?: array<string>
     * }> $givenParams
     */
    public function __construct(private readonly array $givenParams)
    {
    }

    /**
     * @param class-string $namespace
     */
    public function create(string $namespace): Service
    {
        $service = new Service($namespace);
        $tags = $this->transformAbstractToTags($this->getAbstractClasses($namespace));

        if (
            isset($this->givenParams[$namespace])
            && isset($this->givenParams[$namespace]['tags'])
        ) {
            $tags = array_merge($tags, $this->givenParams[$namespace]['tags']);
        }

        if (!empty($tags)) {
            $service->setTags($tags);
        }

        $interfaces = array_values(class_implements($namespace));

        if (!empty($interfaces)) {
            $service->setInterfaces($interfaces);
        }

        if (
            isset($this->givenParams[$namespace])
            && isset($this->givenParams[$namespace]['arguments'])
        ) {
            $service->setGivenArgs($this->givenParams[$namespace]['arguments']);
        }

        return $service;
    }

    /**
     * @return class-string[]
     */
    private function getAbstractClasses(string $namespace): iterable
    {
        while ($parentClass = get_parent_class($namespace)) {
            if ((new \ReflectionClass($parentClass))->isAbstract()) {
                yield $parentClass;
            }
            $namespace = $parentClass;
        }
    }

    /**
     * @param class-string[] $classes
     *
     * @return string[]
     */
    private function transformAbstractToTags(iterable $classes): array
    {
        $tags = [];
        foreach ($classes as $class) {
            $temp = str_split(str_replace('Abstract', '', (new \ReflectionClass($class))->getShortName()));
            $tag = implode(array_map(
                fn ($letter) => ctype_upper($letter) ? '-'.strtolower($letter) : $letter,
                $temp,
            ));
            $tags[] = substr($tag, 1);
        }

        return $tags;
    }
}
