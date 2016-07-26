# Core Module #

## Introduction ##

This is the core dependencies, classes, etc for a Slim module app.

## Installation ##

Use composer to fetch the files and copy files to your project tree:

```
$ composer require martynbiz/slim-module-core
```

Enable the module in src/settings.php:

```
return [
    'settings' => [
        ...
        'module_initializer' => [
            'modules' => [
                ...
                'martynbiz-core' => 'MartynBiz\\Slim\\Module\\Core\\Module',
            ],
        ],
    ],
];
```

## Usage ##

This core application has many dependencies

### Pagination ###
