Skript-Manufaktur -- Simple REST Bundle
=======================================

This Symfony bundle introduces helpers for a REST API. It is optimized to use the `symfony/messenger` component
internally.


## Installation

This package is available on Packagist and should be installed using [Composer](https://getcomposer.org/):

```shell
composer require skript-manufaktur/simple-rest-bundle
```

If you don't use Symfony Flex, you must enable the bundle manually in the application:

```php
return [
    /* ... */
    SkriptManufaktur\SimpleRestBundle\SkriptManufakturSimpleRestBundle::class => ['all' => true],
];
```


## Documentation

The documentation for this bundle will be maintained in the GitHub pages:
https://sengorius.github.io/repositories/simple-rest/index.html


## Tests

Like to validate the tests or check coding standards? Clone the repository, run `composer install` and 
`composer check-bundle` or take a look at the `composer.json` to run single code checks.
