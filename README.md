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
  - '/Entity'
  - <...>
```

### Service config

You can manage in which environment your service must be loaded and the arguments to pass to the constructor.

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

*arguments must be in the same order as in the constructor (ignoring the dependences)*
