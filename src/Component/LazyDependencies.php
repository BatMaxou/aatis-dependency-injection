<?php

namespace Aatis\DependencyInjection\Component;

class LazyDependencies
{
    /**
     * @var Dependency[]|null
     */
    private ?array $dependencies = null;

    /**
     * @param \ReflectionClass<object> $reflexion
     *
     * @return Dependency[] $dependencies
     */
    public function get(\ReflectionClass $reflexion): array
    {
        if ($this->dependencies) {
            return $this->dependencies;
        }

        $dependencies = [];
        $constructor = $reflexion->getConstructor();
        if ($constructor) {
            $parameters = $constructor->getParameters();
            foreach ($parameters as $parameter) {
                $type = $parameter->getType();
                if (!$type || !($type instanceof \ReflectionNamedType)) {
                    throw new \LogicException('Type does not have a name');
                }

                $dependency = new Dependency(
                    $parameter->getName(),
                    $type,
                    $parameter->allowsNull(),
                    $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null,
                );

                $dependencies[] = $dependency;
            }
        }

        return $this->dependencies = $dependencies;
    }
}
