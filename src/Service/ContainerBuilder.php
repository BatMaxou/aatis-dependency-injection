<?php

namespace Aatis\DependencyInjection\Service;

use Aatis\DependencyInjection\Entity\Service;
use Aatis\DependencyInjection\Entity\Container;
use Aatis\DependencyInjection\Exception\FileNotFoundException;
use Symfony\Component\Yaml\Yaml;

/**
 * @phpstan-type ServiceParams array<string, array{
 *  arguments?: array<mixed>,
 *  environment?: array<string>
 * }>
 * @phpstan-type YamlConfig array{
 *  excludes?: array<int, string>,
 *  services?: ServiceParams,
 * }
 * @phpstan-type ComposerJsonConfig array{
 *  autoload: array{
 *     psr-4: array<string, string>
 *  },
 * }
 */
class ContainerBuilder
{
    /**
     * @var array<int, string>
     */
    private array $excludePaths = [];

    /**
     * @var ServiceParams
     */
    private array $givenParams = [];

    private Container $container;

    /** @var ComposerJsonConfig */
    private array $composerJson;

    /**
     * @param array{
     *  env: string
     * } $ctx
     */
    public function __construct(
        private readonly array $ctx,
        private readonly string $sourcePath
    ) {
        $this->getConfig();
    }

    public function build(): Container
    {
        $this->container = new Container();
        $this->registerFolder($this->sourcePath);

        return $this->container;
    }

    private function registerFolder(string $folderPath): void
    {
        if (in_array($this->getShortPath($folderPath), $this->excludePaths)) {
            return;
        }

        $folderContent = array_diff(scandir($folderPath) ?: [], ['..', '.']);

        foreach ($folderContent as $element) {
            $path = $folderPath.'/'.$element;

            if (is_dir($path)) {
                $this->registerFolder($path);

                continue;
            }

            $this->register($path);
        }
    }

    private function register(string $filePath): void
    {
        $shortPath = $this->getShortPath($filePath);
        $namespace = $this->transformToNamespace($filePath);

        if (
            !str_ends_with($shortPath, '.php')
            || in_array($shortPath, $this->excludePaths)
            || interface_exists($namespace)
            || trait_exists($namespace)
            || enum_exists($namespace)
            || !class_exists($namespace)
        ) {
            return;
        } else {
            $reflexion = new \ReflectionClass($namespace);
            if (
                $reflexion->isAbstract()
                || $reflexion->implementsInterface('\Throwable')
            ) {
                return;
            }
        }

        $service = new Service($namespace);
        $tags = $this->transformAbstractToTags($this->getAbstractClasses($namespace));

        if (!empty($tags)) {
            $service->setTags($tags);
        }

        $interfaces = array_values(class_implements($namespace));

        if (!empty($interfaces)) {
            $service->setInterfaces($interfaces);
        }

        if (isset($this->givenParams[$namespace])) {
            if (
                isset($this->givenParams[$namespace]['environment'])
                && !in_array($this->ctx['env'], $this->givenParams[$namespace]['environment'])
            ) {
                return;
            }

            if (isset($this->givenParams[$namespace]['arguments'])) {
                $service->setGivenArgs($this->givenParams[$namespace]['arguments']);
            }
        }
        $this->container->set($namespace, $service);
    }

    private function getShortPath(string $path): string
    {
        return str_replace($_ENV['DOCUMENT_ROOT'].'/../src', '', $path);
    }

    private function transformToNamespace(string $filePath): string
    {
        $autoloaderInfos = $this->composerJson['autoload']['psr-4'];
        $baseNamespace = array_key_first(array_filter($autoloaderInfos, fn ($value) => 'src/' === $value));
        $temp = str_replace($_ENV['DOCUMENT_ROOT'].'/../src/', $baseNamespace ?? 'App\\', $filePath);
        $temp = str_replace(DIRECTORY_SEPARATOR, '\\', $temp);
        $temp = str_replace('.php', '', $temp);

        return $temp;
    }

    private function getConfig(): void
    {
        if (file_exists($_ENV['DOCUMENT_ROOT'].'/../config/services.yaml')) {
            /** @var YamlConfig */
            $config = Yaml::parseFile($_ENV['DOCUMENT_ROOT'].'/../config/services.yaml');
            $this->excludePaths = $config['excludes'] ?? [];
            $this->givenParams = $config['services'] ?? [];
        }

        if (file_exists($_ENV['DOCUMENT_ROOT'].'/../composer.json')) {
            /** @var ComposerJsonConfig */
            $json = json_decode(file_get_contents($_ENV['DOCUMENT_ROOT'].'/../composer.json') ?: '', true);
            $this->composerJson = $json;
        } else {
            throw new FileNotFoundException('composer.json file not found');
        }
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
