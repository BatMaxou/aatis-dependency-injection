<?php

namespace Aatis\DependencyInjection\Service;

use Aatis\DependencyInjection\Component\Container;
use Aatis\DependencyInjection\Exception\ClassNotFoundException;
use Aatis\DependencyInjection\Exception\FileNotFoundException;
use Symfony\Component\Yaml\Yaml;

/**
 * @phpstan-type YamlConfig array{
 *  include_services?: array<int, class-string>,
 *  exclude_paths?: array<int, string>,
 *  services?: array<string, array{
 *      environment?: array<string>,
 *      arguments?: array<mixed>,
 *      tags?: array<string>
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
    private string $sourcePath;

    /**
     * @var array<int, class-string>
     */
    private array $includeServices = [];

    /**
     * @var array<int, string>
     */
    private array $excludePaths = [];

    /**
     * @var array<string, array{
     *  environment?: array<string>,
     *  arguments?: array<mixed>,
     *  tags?: array<string>
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
     * @param mixed[] $ctx
     */
    public function __construct(
        private readonly array $ctx,
    ) {
        $this->sourcePath = $this->ctx['DOCUMENT_ROOT'].'/../src';
        $this->getConfig();
    }

    public function build(): Container
    {
        $this->initializeContainer();
        $this->registerEnv();

        if (!empty($this->includeServices)) {
            $this->registerExtraServices();
        }

        $this->registerFolder($this->sourcePath);

        return $this->container;
    }

    private function initializeContainer(): void
    {
        $serviceTagBuilder = new ServiceTagBuilder();
        $this->serviceFactory = new ServiceFactory($serviceTagBuilder, $this->givenParams);
        $serviceInstanciator = new ServiceInstanciator($this->serviceFactory, $serviceTagBuilder);
        $this->container = new Container($serviceInstanciator);

        $this->container->set(Container::class, $this->serviceFactory->create(Container::class)->setInstance($this->container));
        $this->container->set(ServiceFactory::class, $this->serviceFactory->create(ServiceFactory::class)->setInstance($this->serviceFactory));
        $this->container->set(ServiceInstanciator::class, $this->serviceFactory->create(ServiceInstanciator::class)->setInstance($serviceInstanciator));
    }

    private function registerEnv(): void
    {
        foreach ($this->ctx as $varName => $value) {
            $this->container->set(sprintf('@_%s', $varName), $value);
        }
    }

    private function registerExtraServices(): void
    {
        foreach ($this->includeServices as $namespace) {
            $reflexion = $this->isValidService($namespace);
            if (!$reflexion) {
                throw new ClassNotFoundException($namespace);
            }

            if (!$this->isEnvValid($namespace)) {
                continue;
            }

            $service = $this->serviceFactory
                ->create($namespace)
                ->setReflexion($reflexion);
            $this->container->set($namespace, $service);
        }
    }

    private function registerFolder(string $folderPath): void
    {
        if (in_array($this->getShortPath($folderPath), $this->excludePaths)) {
            return;
        }

        $folderContent = array_diff(scandir($folderPath) ?: [], ['..', '.']);

        foreach ($folderContent as $element) {
            $path = sprintf('%s/%s', $folderPath, $element);

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
        $reflexion = $this->isValidService($namespace, $shortPath);

        if (!$reflexion || !$this->isEnvValid($namespace)) {
            return;
        }

        /** @var class-string $namespace */
        $service = $this->serviceFactory
            ->create($namespace)
            ->setReflexion($reflexion);
        $this->container->set($namespace, $service);
    }

    private function getShortPath(string $path): string
    {
        return str_replace($this->sourcePath, '', $path);
    }

    private function transformToNamespace(string $filePath): string
    {
        $autoloaderInfos = $this->composerJson['autoload']['psr-4'];
        $baseNamespace = array_key_first(array_filter($autoloaderInfos, fn ($value) => 'src/' === $value));
        $temp = str_replace($this->sourcePath.'/', $baseNamespace ?? 'App\\', $filePath);
        $temp = str_replace(DIRECTORY_SEPARATOR, '\\', $temp);
        $temp = str_replace('.php', '', $temp);

        return $temp;
    }

    /**
     * @return \ReflectionClass<object>|false
     */
    private function isValidService(string $namespace, ?string $shortPath = null): \ReflectionClass|false
    {
        if (
            isset($shortPath)
            && (
                !str_ends_with($shortPath, '.php')
                || in_array($shortPath, $this->excludePaths)
            )
        ) {
            return false;
        }

        if (
            interface_exists($namespace)
            || trait_exists($namespace)
            || enum_exists($namespace)
            || !class_exists($namespace)
        ) {
            return false;
        }

        $reflexion = new \ReflectionClass($namespace);
        if (
            $reflexion->isAbstract()
            || $reflexion->implementsInterface('\Throwable')
        ) {
            return false;
        }

        return $reflexion;
    }

    private function isEnvValid(string $namespace): bool
    {
        if (
            isset($this->givenParams[$namespace])
            && isset($this->givenParams[$namespace]['environment'])
            && !in_array($this->ctx['env'], $this->givenParams[$namespace]['environment'])
        ) {
            return false;
        }

        return true;
    }

    private function getConfig(): void
    {
        if (file_exists($this->ctx['DOCUMENT_ROOT'].'/../config/services.yaml')) {
            /** @var YamlConfig */
            $config = Yaml::parseFile($this->ctx['DOCUMENT_ROOT'].'/../config/services.yaml');
            $this->includeServices = $config['include_services'] ?? [];
            $this->excludePaths = $config['exclude_paths'] ?? [];
            $this->givenParams = $config['services'] ?? [];
        }

        if (file_exists($this->ctx['DOCUMENT_ROOT'].'/../composer.json')) {
            /** @var ComposerJsonConfig */
            $json = json_decode(file_get_contents($this->ctx['DOCUMENT_ROOT'].'/../composer.json') ?: '', true);
            $this->composerJson = $json;
        } else {
            throw new FileNotFoundException('composer.json file not found');
        }
    }
}
