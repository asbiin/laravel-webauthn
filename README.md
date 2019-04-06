Webauthn adapter for Laravel
============================

Laravel-Webauthn is an adapter to use Webauthn on Laravel.

[![Latest Version](https://img.shields.io/packagist/v/asbiin/laravel-webauthn.svg?style=flat-square)](https://github.com/asbiin/laravel-webauthn/releases)
[![Downloads](https://img.shields.io/packagist/dt/asbiin/laravel-webauthn.svg?style=flat-square)](https://packagist.org/packages/asbiin/laravel-webauthn)
[![Circle CI](https://img.shields.io/circleci/project/github/asbiin/laravel-webauthn.svg?style=flat-square)](https://circleci.com/gh/asbiin/laravel-webauthn/tree/master)
[![Coverage Status](https://img.shields.io/sonar/https/sonarcloud.io/asbiin_laravel-webauthn/coverage.svg?style=flat-square)](https://sonarcloud.io/dashboard?id=asbiin_laravel-webauthn)

# Installation

You may use Composer to install this package into your Laravel project:

``` bash
composer require asbiin/laravel-webauthn
```

You don't need to add this package to your service providers.

## Support

This package supports Laravel 5.8 and newer, and has been tested with php 7.2 and newer versions.

## Configuration

You can publish the Laravel Webauthn configuration in a file named `config/webauthn.php`.
Simply run this artisan command:

``` bash
php artisan laravelwebauthn:publish
```

If desired, you may disable LaravelWebauthn entirely using the `enabled` configuration option:
``` php
'enabled' => false,
```


# Usage

## Add middleware

Add this in your `$routeMiddleware`, in `app/Http/Kernel.php` file:

```php
'webauthn' => \LaravelWebauthn\Http\Middleware\WebauthnMiddleware::class,
```

You can use this middleware in your `routes.php` file:
```php
Route::middleware(['auth', 'webauthn'])->group(function () {
  ...
}
```

## Urls

These url are used

* GET `/webauthn/auth`: login page
* POST `/webauthn/auth`: post datas after WebAuthn validate
* GET `/webauthn/register`: get datas to register a new key
* POST `/webauthn/register`: post datas after WebAuthn check
* DELETE `/webauthn/{id}`: get register datas

## Events

Events are dispatched by LaravelWebauthn:

* `\LaravelWebauthn\Events\WebauthnLoginData` on creating authentication datas
* `\LaravelWebauthn\Events\WebauthnLogin` on login with WebAuthn check
* `\LaravelWebauthn\Events\WebauthnRegisterData` on creating register datas
* `\LaravelWebauthn\Events\WebauthnRegister` on registering a new key


# License

Author: [Alexis Saettler](https://github.com/asbiin)

Copyright © 2019.

Licensed under the MIT License. [View license](/LICENSE).
