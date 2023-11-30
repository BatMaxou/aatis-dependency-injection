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
    'env' => 'app_environment',
]
```

Create the conatiner builder with the context and the path to your PHP sources folder

```php
(new ContainerBuilder($ctx, 'PATH/TO/SOURCES'))->build();
```

### Exclude files

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
```

*environment is optional*

*the key of an argument must have the same name as in the constructor*

### Interface into constructor

When an interface is requested into the constructor of a service, the DI will try to find a service implementing this interface into your app.

If several services implement the interface, the DI will pick the first one found.

If you want to use a specific class, don't forget to declare it into the config/services.yaml file.

```yaml
# In config/services.yaml file :

services:
    Namespace\To\The\Service:
        arguments:
            variable_name_into_the_constructor: 'service_implementing_the_interface'
```

Otherwise if your want to use a specific class of the vendor, do the prevous step and precise the class of it to the target service.

```yaml
# In config/services.yaml file :

include_services:
    - 'Namespace\To\The\Vendor\Service\Implementing\The\Interface'
```
