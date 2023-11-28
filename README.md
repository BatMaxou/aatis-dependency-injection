# Aatis DI

## Installation

```bash
composer require aatis/dependency-injection
```

## Usage

### Requirements

Set your context

```php
$ctx = [
    'env' => 'your_app_environment',
]
```

Create the conatiner builder with the context and the path to your PHP sources folder

```php
(new ContainerBuilder($ctx, 'PATH/TO/SOURCES'))->build();
```

### Exclude files

In config/services.yaml file :

```yaml
excludes:
  - '/Folder'
  - '/OtherFolder/file.txt'
  - <...>
```

### Service config

You can manage in which environment your service must be loaded and the arguments to pass to the constructor.

You can also precise the class to use for the dependency when it is an interface. 

In config/services.yaml file :

```yaml
services:
    Namespace\To\Your\Service:
        environment:
            - 'env_name1'
            - 'env_name2'
            - <...>
        arguments:
            variable_name_into_the_constructor: 'your_value'
```

*environment is optional*

*the key of an argument must have the same name as in the constructor*

### Interface into constructor

When an interface is requested into the constructor of a service, the DI will try to find a service implementing this interface into your app.

If several services implement the interface, the DI will pick the first one found.

Otherwise, if you want to use a specific class or a class of your vendor, don't forget to declare it into the config/services.yaml file.

In config/services.yaml file :

```yaml
services:
    Namespace\To\Your\Service:
        arguments:
            variable_name_into_the_constructor: 'your_class_implementing_the_interface'
```
