Webauthn adapter for Laravel <!-- omit in toc -->
============================

[![Latest Version](https://img.shields.io/packagist/v/asbiin/laravel-webauthn.svg?style=flat-square&label=Latest%20Version)](https://github.com/asbiin/laravel-webauthn/releases)
[![Downloads](https://img.shields.io/packagist/dt/asbiin/laravel-webauthn.svg?style=flat-square&label=Downloads)](https://packagist.org/packages/asbiin/laravel-webauthn)
[![Workflow Status](https://img.shields.io/github/workflow/status/asbiin/laravel-webauthn/Laravel%20WebAuthn%20workflow?style=flat-square&label=Workflow%20Status)](https://github.com/asbiin/laravel-webauthn/actions?query=branch%3Amain)
[![Quality Gate](https://img.shields.io/sonar/quality_gate/asbiin_laravel-webauthn?server=https%3A%2F%2Fsonarcloud.io&style=flat-square&label=Quality%20Gate)](https://sonarcloud.io/dashboard?id=asbiin_laravel-webauthn)
[![Coverage Status](https://img.shields.io/sonar/coverage/asbiin_laravel-webauthn?server=https%3A%2F%2Fsonarcloud.io&style=flat-square&label=Coverage%20Status)](https://sonarcloud.io/dashboard?id=asbiin_laravel-webauthn)


- [Features](#features)
- [Installation](#installation)
- [Set Up](#set-up)
  - [Add LaravelWebauthn middleware](#add-laravelwebauthn-middleware)
  - [Login via remember](#login-via-remember)
  - [Passwordless authentication](#passwordless-authentication)
  - [Disabling Views](#disabling-views)
- [Usage](#usage)
  - [Authenticate](#authenticate)
  - [Register a new key](#register-a-new-key)
  - [Routes](#routes)
  - [Events](#events)
  - [View response](#view-response)
- [Compatibility](#compatibility)
  - [Laravel compatibility](#laravel-compatibility)
  - [Browser compatibility](#browser-compatibility)
    - [Homestead](#homestead)
- [License](#license)


**LaravelWebauthn** is an adapter to use Webauthn as [2FA](https://en.wikipedia.org/wiki/Multi-factor_authentication) (two-factor authentication) or as passwordless authentication on Laravel.

**Try this now on the [demo application](https://laravel-webauthn-example.herokuapp.com/).**


# Features

- Manage Webauthn keys registration
- 2nd factor authentication: add a middleware service to use a Webauthn key as 2FA
- Login provider using a Webauthn key, without password


# Installation

First install LaravelWebauthn using the Composer package manager:

```sh
composer require asbiin/laravel-webauthn
```

Next, install a [`psr/http-factory-implementation` implementation](https://packagist.org/providers/psr/http-factory-implementation).

You can either
- install `guzzlehttp/psr7` package:
    ```sh
    composer require guzzlehttp/psr7
    ```
- or any other implementation, like `nyholm/psr7`. You'll also need `symfony/psr-http-message-bridge` and `php-http/discovery`:
    ```sh
    composer require symfony/psr-http-message-bridge php-http/discovery nyholm/psr7
    ```

You can publish LaravelWebauthn configuration in a file named `config/webauthn.php`, and resources using the `vendor:publish` command:

```sh
php artisan vendor:publish --provider="LaravelWebauthn\WebauthnServiceProvider"
```

If desired, you may disable LaravelWebauthn entirely using the `enabled` configuration option:
```php
    'enabled' => false,
```

Next, you should migrate your database:

```sh
php artisan migrate
```


# Set Up

## Add LaravelWebauthn middleware

The Webauthn middleware will force the user to authenticate their webauthn key for cetain routes.

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

The Webauthn middleware will redirect the user to the webauthn login page when required.


## Login via remember

When session expires, but the user have set the `remember` token, you can revalidate webauthn session by adding this in your `App\Providers\EventServiceProvider` file:

```php
use Illuminate\Auth\Events\Login;
use LaravelWebauthn\Listeners\LoginViaRemember;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Login::class => [
            LoginViaRemember::class,
        ],
    ];
// ...
```


## Passwordless authentication

You can use Webauthn to authenticate a user without a password, using only a webauthn key authentication.

To enable passwordless authentication, first add the webauthn user provider: update your `config/auth.php` file and change the `users` provider:

```php
    'providers' => [
        'users' => [
            'driver' => 'webauthn',
            'model' => App\Models\User::class,
        ],
    ],
```

Then allow your login page to initiating a webauthn login with an `email` identifier.

You can call `webauthn.auth.options` route with a POST request and an `email` input to get the challenge data.
See [authentation](#Authenticate) section for more details.


## Disabling Views

By default LaravelWebauthn defines routes that are intended to return views for authentication and register key.

However, if you are building a JavaScript driven single-page application, you may not need these routes. For that reason, you may disable these routes entirely by setting the `views` configuration value within your application's `config/webauthn.php` configuration file to false:

```php
'views' => false,
```


# Usage

You will find an example of usage on [asbiin/laravel-webauthn-example](https://github.com/asbiin/laravel-webauthn-example). You can try it right now on the [demo application](https://laravel-webauthn-example.herokuapp.com/).


## Authenticate

To authenticate with a webauthn key, the workflow is the following:
1. Open the `webauthn.login` login page.
   You can customize the login page view by calling `Webauthn::loginViewResponseUsing`. See [View response](#view-response)

   The default behavior will open [webauthn::authenticate](/resources/views/authenticate.blade.php) page.
   You can also change the value of `webauthn.views.authenticate` in the configuration file.

2. Or: Get the publicKey challenge by calling `webauthn.auth.options` (if not provided).

3. Start the webauthn browser authentication.
   You can use the [`webauthn.js`](/resources/js/webauthn.js) library to do this.

   Send the signed data to `webauthn.auth` route.

4. The POST response will be:
   - a redirect response
   - or a json response with a `callback` data.


Example:

```html
  <!-- load javascript part -->
  <script src="{!! secure_asset('vendor/webauthn/webauthn.js') !!}"></script>
...
  <!-- script part to run the sign part -->
  <script>
    var publicKey = {!! json_encode($publicKey) !!};

    var webauthn = new WebAuthn();

    webauthn.sign(
      publicKey,
      function (data) {
        axios.post("{{ route('webauthn.auth') }}", data)
          .then(function (response) {
            if (response.data.callback) { window.location.href = response.data.callback;}
          });
      }
    );
  </script>
```

If the authentication is successful, the server will use the `webauthn.redirects.login` configuration:
  - to redirect the response on a plain http call
  - or with a json response, like:
    ```javascript
    {
        result: true,
        callback: `webauthn.redirects.login` target url,
    }
    ```

## Register a new key

To register a new webauthn key, the workflow is the following:
1. Open the `webauthn.register` page.
   You can customize the register page view by calling `Webauthn::registerViewResponseUsing`. See [View response](#view-response)

   The default behavior will open [webauthn::register](/resources/views/register.blade.php) page.
   You can also change the value of `webauthn.views.register` in the configuration file.

2. Or: Get the publicKey challenge by calling `webauthn.store.options` (if not provided).

3. Start the webauthn browser registration.
   You can use the [`webauthn.js`](/resources/js/webauthn.js) library to do this.

   Send the signed data to `webauthn.store` route.
   The data should contain a `name` field with the webauthn key name.

4. The POST response will be:
   - a redirect response
   - or a json response with a `callback` data.


Example:

```html
  <!-- load javascript part -->
  <script src="{!! secure_asset('vendor/webauthn/webauthn.js') !!}"></script>
...
  <!-- script part to run the sign part -->
  <script>
    var publicKey = {!! json_encode($publicKey) !!};

    var webauthn = new WebAuthn();

    webauthn.register(
      publicKey,
      function (data) {
        axios.post("{{ route('webauthn.store') }}", {
          ...data,
          name: "{{ $name }}",
        })
      }
    );
  </script>
```

If the registration is successful, the server will use the `webauthn.redirects.register` configuration:
  - to redirect the response on a plain http call
  - or with a json response, like:
    ```javascript
    {
        result: json serialized webauthn key value,
        callback: `webauthn.redirects.register` target url,
    }
    ```


## Routes

These reoutes are defined:

| Request | Route | Description |
|---------|-------|-------------|
| GET `/webauthn/auth` | `webauthn.login` | The login page. |
| POST `/webauthn/auth/options` | `webauthn.auth.options` | Get the publicKey and challenge to initiate a WebAuthn login. |
| POST `/webauthn/auth` | `webauthn.auth` | Post data after a WebAuthn login validate. |
| GET `/webauthn/keys/create` | `webauthn.create` | The register key page. |
| POST `/webauthn/keys/options` | `webauthn.store.options` | Get the publicKeys and challenge to initiate a WebAuthn registration. |
| POST `/webauthn/keys` | `webauthn.store` | Post data after a WebAuthn register check. |
| DELETE `/webauthn/keys/{id}` | `webauthn.destroy` | Delete an existing key. |
| PUT `/webauthn/keys/{id}` | `webauthn.update` | Update key properties (name, ...). |


You can customize the first part of the url by setting `prefix` value in the config file.

## Events

Events are dispatched by LaravelWebauthn:

* `\LaravelWebauthn\Events\WebauthnLogin` on login with Webauthn check.
* `\LaravelWebauthn\Events\WebauthnLoginData` on preparing authentication data challenge.
* `\Illuminate\Auth\Events\Failed` on a failed login check.
* `\LaravelWebauthn\Events\WebauthnRegister` on registering a new key.
* `\LaravelWebauthn\Events\WebauthnRegisterData` on preparing register data challenge.
* `\LaravelWebauthn\Events\WebauthnRegisterFailed` on failing registering a new key.


## View response

You can easily change the view responses with the Webauthn service:

```php
use LaravelWebauthn\Services\Webauthn;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        Webauthn::loginViewResponseUsing(LoginViewResponse::class);
    }
}
```

```php
use LaravelWebauthn\Http\Responses\LoginViewResponse as LoginViewResponseBase;

class LoginViewResponse extends LoginViewResponseBase
{
    public function toResponse($request)
    {
        $publicKey = $this->publicKeyRequest($request);

        return Inertia::render('Webauthn/WebauthnLogin', [
            'publicKey' => $publicKey
        ]);
    }
}
```

List of methods and their expected response contracts:

| Webauthn static methods | `\LaravelWebauthn\Contracts` |
|-------------------------|------------------------------|
| `loginViewResponseUsing` | `LoginViewResponseContract` |
| `loginSuccessResponseUsing` | `LoginSuccessResponseContract` |
| `registerViewResponseUsing` | `RegisterViewResponseContract` |
| `registerSuccessResponseUsing` | `RegisterSuccessResponseContract` |
| `destroyViewResponseUsing` | `DestroyResponseContract` |
| `updateViewResponseUsing` | `UpdateResponseContract` |


# Compatibility

## Laravel compatibility

This package has the following Laravel compatibility:

| Laravel  | [asbiin/laravel-webauthn](https://github.com/asbiin/laravel-webauthn) |
|----------|----------|
| 5.8-8.x  | <= 1.2.0 |
| 7.x-8.x  | >= 2.0.0 |

## Browser compatibility

Most of the browsers [support Webauthn](https://caniuse.com/webauthn).

However, your browser will refuse to negotiate a relay to your security device without the following:

- a proper domain (localhost and 127.0.0.1 will be rejected by `webauthn.js`)
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


# License

Author: [Alexis Saettler](https://github.com/asbiin)

Copyright © 2019–2022.

Licensed under the MIT License. [View license](/LICENSE.md).
