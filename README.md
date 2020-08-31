Webauthn adapter for Laravel
============================

LaravelWebauthn is an adapter to use Webauthn on Laravel.

[![Latest Version](https://img.shields.io/packagist/v/asbiin/laravel-webauthn.svg?style=flat-square)](https://github.com/asbiin/laravel-webauthn/releases)
[![Downloads](https://img.shields.io/packagist/dt/asbiin/laravel-webauthn.svg?style=flat-square)](https://packagist.org/packages/asbiin/laravel-webauthn)
[![GitHub Workflow Status](https://img.shields.io/github/workflow/status/asbiin/laravel-webauthn/Laravel%20WebAuthn%20workflow?style=flat-square)](https://github.com/asbiin/laravel-webauthn/actions?query=branch%3Amaster)
[![Sonar Quality Gate](https://img.shields.io/sonar/quality_gate/asbiin_laravel-webauthn?server=https%3A%2F%2Fsonarcloud.io&style=flat-square)](https://sonarcloud.io/dashboard?id=asbiin_laravel-webauthn)
[![Coverage Status](https://img.shields.io/sonar/https/sonarcloud.io/asbiin_laravel-webauthn/coverage.svg?style=flat-square)](https://sonarcloud.io/dashboard?id=asbiin_laravel-webauthn)


# Installation

You may use Composer to install this package into your Laravel project:

``` bash
composer require asbiin/laravel-webauthn
```

You don't need to add this package to your service providers.

## Support

This package supports Laravel 5.8 and newer, and has been tested with php 7.2 and newer versions.

It's based on [web-auth/webauthn-framework](https://github.com/web-auth/webauthn-framework).


## Important

Your browser will refuse to negotiate a relay to your security device without the following:

- domain (localhost and 127.0.0.1 will be rejected by `webauthn.js`)
- an SSL/TLS certificate trusted by your browser (self-signed is okay)
- connected HTTPS on port 443 (ports other than 443 will be rejected)

### Homestead
If you are a Laravel Homestead user, the default is to forward ports. You can switch from NAT/port forwarding to a private network with similar `Homestead.yaml` options:

```yaml
sites:
  - map: homestead.test
networks:
  - type: "private_network"
    ip: "192.168.254.2"
```

Re-provisioning vagrant will inform your virtual machine of the new network and install self-signed SSL/TLS certificates automatically: `vagrant reload --provision`

If you haven't done so already, describe your site domain and network in your hosts file:
```
192.168.254.2 homestead.test
```


## Configuration

You can publish the LaravelWebauthn configuration in a file named `config/webauthn.php`, and resources.
Just run this artisan command:

```sh
php artisan laravelwebauthn:publish
```

If desired, you may disable LaravelWebauthn entirely using the `enabled` configuration option:
``` php
'enabled' => false,
```

### Add LaravelWebauthn middleware

Add this in the `$routeMiddleware` array of your `app/Http/Kernel.php` file:

```php
  'webauthn' => \LaravelWebauthn\Http\Middleware\WebauthnMiddleware::class,
```

You can use this middleware in your `routes.php` file:
```php
Route::middleware(['auth', 'webauthn'])->group(function () {
    Route::get('/home', 'HomeController@index')->name('home');
    ...
}
```

This way users would have to validate their key on login.



# Usage

You will find an example of usage on this repository: [asbiin/laravel-webauthn-example](https://github.com/asbiin/laravel-webauthn-example).


## Authenticate

The middleware will open the page defined in `webauthn.authenticate.view` configuration.
The default value will open [webauthn::authenticate](/resources/views/authenticate.blade.php) page. The basics are:

```html
  <!-- load javascript part -->
  <script src="{!! secure_asset('vendor/webauthn/webauthn.js') !!}"></script>
...
  <!-- form to send datas to -->
  <form method="POST" action="{{ route('webauthn.auth') }}" id="form">
    @csrf
    <input type="hidden" name="data" id="data" />
  </form>
...
  <!-- script part to run the sign part -->
  <script>
    var publicKey = {!! json_encode($publicKey) !!};

    var webauthn = new WebAuthn();

    webauthn.sign(
      publicKey,
      function (datas) {
        $('#data').val(JSON.stringify(datas)),
        $('#form').submit();
      }
    );
  </script>
```

The `webauthn.authenticate.postSuccessCallback` configuration is used to redirect the submit form to the callback url: it's the page the user tried to access first.

If the value is false, the `webauthn.authenticate.postSuccessRedirectRoute` is used as a redirect route.

If `postSuccessCallback` is false and `postSuccessRedirectRoute` is empty, the return will be JSON form:
```javascript
{
    result: true,
    callback: 'http://localhost',
}
```


## Register a new key

To register a new key, open `/webauthn/register` or go to `route('webauthn.register')`, or any of your implementation.

The controller will open the page defined in `webauthn.register.view` configuration.
The default value will open [webauthn::register](/resources/views/register.blade.php) page. The basics are:

```html
  <!-- load javascript part -->
  <script src="{!! secure_asset('vendor/webauthn/webauthn.js') !!}"></script>
...
  <!-- form to send datas to -->
  <form method="POST" action="{{ route('webauthn.auth') }}" id="form">
    @csrf
    <input type="hidden" name="register" id="register" />
    <input type="hidden" name="name" id="name" />
  </form>
...
  <!-- script part to run the sign part -->
  <script>
    var publicKey = {!! json_encode($publicKey) !!};

    var webauthn = new WebAuthn();

    webauthn.register(
      publicKey,
      function (datas) {
        $('#register').val(JSON.stringify(datas)),
        $('#form').submit();
      }
    );
  </script>
```

The `webauthn.register.postSuccessRedirectRoute` configuration is used to redirect the submit form after the registration.

If `postSuccessRedirectRoute` is empty, the return will be JSON form:
```javascript
{
    result: true,
    id: 42,
    object: 'webauthnKey',
    name: 'name of the key',
    counter: 12,
}
```


## Urls

These url are used

* GET `/webauthn/auth` / `route('webauthn.login')`
  The login page.

* POST `/webauthn/auth` / `route('webauthn.auth')`
  Post datas after a WebAuthn login validate.

* GET `/webauthn/register` / `route('webauthn.register')`
  Get datas to register a new key

* POST `/webauthn/register` / `route('webauthn.create')`
  Post datas after a WebAuthn register check

* DELETE `/webauthn/{id}` / `route('webauthn.destroy')`
  Get register datas


## Events

Events are dispatched by LaravelWebauthn:

* `\LaravelWebauthn\Events\WebauthnLoginData` on creating authentication datas
* `\LaravelWebauthn\Events\WebauthnLogin` on login with WebAuthn check
* `\LaravelWebauthn\Events\WebauthnRegisterData` on creating register datas
* `\LaravelWebauthn\Events\WebauthnRegister` on registering a new key


# License

Author: [Alexis Saettler](https://github.com/asbiin)

Copyright © 2019-2020.

Licensed under the MIT License. [View license](/LICENSE).
