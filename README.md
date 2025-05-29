# Aatis Dependency Injection

## Installation

```bash
composer require aatis/dependency-injection
```

## Dependencies

- `aatis/parameter-bag` (https://github.com/BatMaxou/aatis-parameter-bag)
- `aatis/tag` (https://github.com/BatMaxou/aatis-tag)

## Usage

### Requirements

Set the environment variable `APP_ENV` to the name of the environment you want to use.

Create the container builder with the context of your app (`$_SERVER`).

```php
(new ContainerBuilder($ctx))->build();
```

### Exclude files

Precise the files that are not services.

```yaml
# In config/services.yaml file :

exclude_paths:
  - "/Folder"
  - "/OtherFolder/file.txt"
  - <...>
```

```php
// Directly in PHP code when building the container :

(new ContainerBuilder($ctx))
    ->excludePaths('/Folder')
    ->excludePaths('/OtherFolder/file.txt')
    ->build();
```

### Service config

You can manage in which environment your service must be loaded and the arguments to pass to the constructor.

Finally, you can give extra tags to any service.

```yaml
# In config/services.yaml file :

services:
    Namespace\Of\Service:
        environment:
            - "env_name1"
            - "env_name2"
            - <...>
        arguments:
            variable_name: "value"
        tags:
            - "tag_name_1"
            - { tag: "tag_name_2", priority: 10 }
            - <...>
```

```php
// Directly in PHP code when building the container :

(new ContainerBuilder($ctx))
    ->register(Service::class, [
        'environment' => ['env_name1', 'env_name2'],
        'arguments' => [
            'variable_name' => 'value',
        ],
        'tags' => [
            'tag_name_1',
            ['tag' => 'tag_name_2', 'priority' => 10],
        ],
    ])
    ->build();
```

> [!NOTE]
> The key of an argument must have the same name as in the constructor

> [!NOTE]
> Tags have priority set to 0 by default. You can set it to any number you want.
> Services will be sorted by highest priority first when the `Container` return multiple services. 

> [!NOTE]
> It is also possible to define a configuration for an abstract class.
> This configuration will be used for all the services extending this class, and will be merged with the configuration of the service itself if provided.

### Interface into constructor

When an interface is requested into the constructor of a service, the `Container` will try to find a service implementing this interface into your app.

> [!NOTE]
> If multiple services implement the interface, the `Container` will pick the one with the highest priority.
> If many services implementing the interface share the highest priority, the `Container` will priorise an already instancied service.
> Otherwise, it will pick the first one found.

If you want to use a specific service, don't forget to declare it into the configuration of the service.
 
```yaml
# In config/services.yaml file :

services:
    Namespace\Of\Service:
        arguments:
            variable_name: Namespace\Of\Service\Wanted\With\The\Interface
```

> [!WARNING]
> If your want to use a specific service of the vendor, do the previous step and precise it into the `includes_services` part of the config.

```yaml
# In config/services.yaml file :

include_services:
    - Namespace\Of\The\Vendor\Service\Implementing\The\Interface
```

### Env variable into constructor

You can request for a env variable directly into the constructor of a service.

```php
public function __construct(string $_env_var)
{
    // ...
}
```

> [!NOTE]
> The name of the variable must start with $_ and be followed by the env variable name in lowercase.

### Container uses

#### Get and Set

With the container, you can get and set any service / env variable (prefixed by `@_`) you want with the methods `get()` and `set()`.

However, to set a service, you must give an instance of the `Service` class.
You can create it with the `ServiceFactory` service.

```php
// Env Variable
$container->get('@_ENV_VAR_NAME');
$container->set('@_ENV_VAR_NAME', 'value');

// Service
$container->get(Service::class);

$service = $container->get(ServiceFactory::class)->create(Service::class);
$container->set(Service::class, $service);
``` 

#### Get by tag

You can get services by tag using the `ServiceTagBuilder`:

```php
$tagBuilder = $container->get(ServiceTagBuilder::class);

// returns the instance of the services tagged
$taggedServiceInstances = $container->get($tagBuilder->buildFromName('tag_name_1'));

// returns the Service component instances of the services tagged
$taggedServices = $container->get($tagBuilder->buildFromName('tag_name_1', [ServiceTagOption::SERVICE_TARGETED]));
```

#### Get by interface

You can easily get services implementing an interface using `ServiceTagBuilder` with the `buildFromInterface()` method.

```php
$tagBuilder = $container->get(ServiceTagBuilder::class);
$taggedServiceInstances = $container->get($tagBuilder->buildFromInterface(Interface::class));
```

#### Get single service

You can get the `Service` of a single service using `ServiceTagOption::FROM_CLASS` and `ServiceTagOption::SERVICE_TARGETED` options.

```php
$tagBuilder = $container->get(ServiceTagBuilder::class);

// returns the Service component instance of the service targeted
$service = $container->get($tagBuilder->buildFromName(Service::class, [ServiceTagOption::FROM_CLASS, ServiceTagOption::SERVICE_TARGETED]));

// returns the instance of the service targeted (same as $container->get(Service::class))
$serviceInstance = $container->get($tagBuilder->buildFromName(Service::class, [ServiceTagOption::FROM_CLASS]));
```

### AsDefaultTaggedService

As said before, the priority of a tag is set to 0 by default, but when attaching the attribute `AsDefaultTaggedService` to a service, you can set the priority to some or all of his tags to 1.

```php
<?php
#[AsDefaultTaggedService]
class ServiceWithTags
{
}

#[AsDefaultTaggedService(['tag_name_1', 'tag_name_2'])]
class AnotherServiceWithTags
{
}
```

> [!NOTE]
> If you set the priority of the service into the config, it will override the one set by this attribute.

### ServiceFactory

You can use the `ServiceFactory` service to create a service instance.

```php
$service = $container->get(ServiceFactory::class)->create(Service::class);
```

> [!CAUTION]
> If the package is properly configured, you should not need to use this service.

### ServiceInstanciator

You can use the `ServiceInstanciator` service and the `setInstance()` method of the `Service` class to instanciate a service into the container.

You can choose between two methods to instanciate a service. For the first one, you must inform the arguments to pass to the constructor into the config. For the second one, you must create the instance yourself.

```php
$service = $container->get(ServiceFactory::class)->create(Service::class);

// Method 1
$instance = $container->get(ServiceInstanciator::class)->instanciate($service)

// Method 2
$instance = new Service($arg1, $arg2, ...);

$service->setInstance($instance);
$container->set(Service::class, $service);
```

> [!CAUTION]
> If the package is properly configured, you should not need to use this service.
