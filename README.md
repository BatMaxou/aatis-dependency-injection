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
  - '/Folder'
  - '/OtherFolder/file.txt'
  - <...>
```

### Service config

You can manage in which environment your service must be loaded and the arguments to pass to the constructor.

You can also precise the class to use for the dependency when it is an interface.

Finally, you can give extra tags to any service.

```yaml
# In config/services.yaml file :

services:
    Namespace\Of\Service:
        environment:
            - 'env_name1'
            - 'env_name2'
            - <...>
        arguments:
            variable_name: 'value'
        tags:
            - 'tag_name_1'
            - { tag: "tag_name_2", priority: 10 }
            - <...>
```

> [!NOTE]
> The key of an argument must have the same name as in the constructor*


> [!NOTE]
> Tags has priority set to 0 by default. You can set it to any value you want.
> Services will be sorted by highest priority first when the `Container` return multiple services. 

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
            variable_name: 'Namespace\Of\Service\Wanted\With\The\Interface'
```

> [!WARNING]
> If your want to use a specific service of the vendor, do the previous step and precise it into the `includes_services` part of the config.

```yaml
# In config/services.yaml file :

include_services:
    - 'Namespace\Of\The\Vendor\Service\Implementing\The\Interface'
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

With the container, you can get and set any service / env variable you want with the methods `get()` and `set()`.

However, to set a service, you must give an instance of the `Service` class.
You can create it with the `ServiceFactory` service.

```php
// Env Variable
$container->get('ENV_VAR_NAME');
$container->set('ENV_VAR_NAME', 'value');

// Service
$container->get(Service::class);

$service = $container->get(ServiceFactory::class)->create(Service::class);
$container->set(Service::class, $service);
``` 

#### Get by tag

You can get services by tag using the following prefix:

- `@tag_` to get the instance of the service tagged.
- `@service_of_tag_` to get the `Service` component instance of the service tagged into the container.

> [!TIP]
> It is recommended to inject the `ServiceTagBuilder` and to generate tags with it.

```php

// With ServiceTagBuilder
$tagBuilder = $container->get(ServiceTagBuilder::class);
$taggedServiceInstances = $container->get($tagBuilder->buildFromName('tag_name_1')); // returns the instance of the service tagged
$taggedServices = $container->get($tagBuilder->buildFromName('tag_name_1', [ServiceTagOption::SERVICE_TARGETED])); // returns the Service component instance of the service tagged

// Without ServiceTagBuilder
$taggedServices = $container->get('@tag_name_1');
```

#### Get by interface

You can easily get services implementing an interface using `ServiceTagBuilder` with the `buildFromInterface()` method.

```php
$tagBuilder = $container->get(ServiceTagBuilder::class);
$taggedServiceInstances = $container->get($tagBuilder->buildFromInterface(Interface::class));
```

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
