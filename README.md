Skript-Manufaktur -- Simple REST Bundle
=======================================

This is a Symfony bundle is that introduces helpers for a REST API. It is optimized to use the `symfony/messenger`
component internally.


## Installation

Open your projects `composer.json` file and add the repository:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/Sengorius/simple-rest-bundle.git"
        }
    ]
}
```

Then add the component via composer with `composer req skript-manufaktur/simple-rest-bundle`.

As this bundle uses the `symfony/flex` mechanics, your `config/bundles.php` should already contain a new line,
otherwise open the file and add:

```php
return [
    /* ... */
    SkriptManufaktur\SimpleRestBundle\SkriptManufakturSimpleRestBundle::class => ['all' => true],
];
```
