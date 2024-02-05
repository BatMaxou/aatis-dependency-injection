# Aatis DI

## Installation

```bash
composer require aatis/dependency-injection
```

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

You can also precise the class to use for the dependency when it's an interface.

Finally, you can give extra tags to any service (which you can access with the `getByTag()` method of the container).

```yaml
# In config/services.yaml file :

services:
    Namespace\To\The\Service:
        environment:
            - 'env_name1'
            - 'env_name2'
            - <...>
        arguments:
            variable_name_into_the_constructor: 'it_value'
        tags:
            - 'tag_name1'
            - 'tag_name2'
            - <...>
```

*environment and tags are optional*

*the key of an argument must have the same name as in the constructor*

### Interface into constructor

When an interface is requested into the constructor of a service, the DI will try to find a service implementing this interface into your app.

If several services implement the interface, the DI will pick the first one found.

If you want to use a specific service, don't forget to declare it into the declaration of the service.

```yaml
# In config/services.yaml file :

services:
    Namespace\To\The\Service:
        arguments:
            variable_name_into_the_constructor: 'service_implementing_the_interface'
```

Otherwise if your want to use a specific service of the vendor, do the previous step and precise it into the `includes_services` part of the config.

```yaml
# In config/services.yaml file :

include_services:
    - 'Namespace\To\The\Vendor\Service\Implementing\The\Interface'
```

### Env variable into constructor

You can request for a env variable directly into the constructor of a service.

```php
public function __construct(string $_my_env_var)
{
    // ...
}
```

*the name of the variable must start with $_ and be followed by the env variable name in lowercase*

### Container uses

With the container, you can get and set any service / env variable you want with the methods `get()` and `set()` of the container.

However, to set a service, you must give an instance of the `Aatis\DependencyInjection\Entity\Service`. You can create it with the `Aatis\DependencyInjection\Service\ServiceFactory` class.

```php
// Env Variable
$container->get('APP_ENV_VAR_NAME');
$container->set('APP_ENV_VAR_NAME', 'value');

// Service
$container->get('Namespace\To\The\Service');

$service = $container->get(ServiceFactory::class)->create('Namespace\To\The\Service');
$container->set('Namespace\To\The\Service', $service);
``` 

*APP_ENV_VAR_NAME must start with "APP_"*

### ServiceInstanciator

You can use the `ServiceInstanciator` class and the `setInstance()` method of the `Aatis\DependencyInjection\Entity\Service` to instanciate a service into the container.

You can choose between two methods to instanciate a service. For the first one, you must inform the arguments to pass to the constructor into the config. For the second one, you must create the instance yourself.

```php
$service = $container->get(ServiceFactory::class)->create('Namespace\To\The\Service');

// Method 1
$instance = $container->get(ServiceInstanciator::class)->instanciate($service)

// Method 2
$instance = new Namespace\To\The\Service();

$service->setInstance($instance);
$container->set('Namespace\To\The\Service', $service);
