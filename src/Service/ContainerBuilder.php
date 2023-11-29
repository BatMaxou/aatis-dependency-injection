<?php

namespace Aatis\DependencyInjection\Service;

use Symfony\Component\Yaml\Yaml;
use Aatis\DependencyInjection\Exception\FileNotFoundException;

/**
 * @phpstan-type YamlConfig array{
 *  excludes?: array<int, string>,
 *  services?: array<string, array{
 *      arguments?: array<mixed>,
 *      environment?: array<string>
 *  }>
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
     * @var array<string, array{
     *  arguments?: array<mixed>,
     *  environment?: array<string>
     * }>
     */
    private array $givenParams = [];

    private Container $container;

    private ServiceFactory $serviceFactory;

    /**
     * @var ComposerJsonConfig
     */
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
        $this->initializeContainer();
        $this->registerFolder($this->sourcePath);

        return $this->container;
    }

    private function initializeContainer(): void
    {
        $this->serviceFactory = new ServiceFactory($this->givenParams);
        $serviceInstanciator = new ServiceInstanciator($this->serviceFactory);
        $this->container = new Container($serviceInstanciator);

        $this->container->set(Container::class, $this->serviceFactory->create(Container::class)->setInstance($this->container));
        $this->container->set(ServiceFactory::class, $this->serviceFactory->create(ServiceFactory::class)->setInstance($this->serviceFactory));
        $this->container->set(ServiceInstanciator::class, $this->serviceFactory->create(ServiceInstanciator::class)->setInstance($serviceInstanciator));
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

        if (
            isset($this->givenParams[$namespace])
            && isset($this->givenParams[$namespace]['environment'])
            && !in_array($this->ctx['env'], $this->givenParams[$namespace]['environment'])
        ) {
            return;
        }

        $service = $this->serviceFactory->create($namespace);
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
}
