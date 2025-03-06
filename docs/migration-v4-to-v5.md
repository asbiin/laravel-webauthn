# Migration from v4 to v5

V5 of Webauthn introduces multiple breaking changes:

## Classes and Interfaces

The [`Webauthn\PublicKeyCredentialRequestOptions`](https://github.com/web-auth/webauthn-framework/blob/5.2.x/src/webauthn/src/PublicKeyCredentialRequestOptions.php) and [`Webauthn\PublicKeyCredentialCreationOptions`](https://github.com/web-auth/webauthn-framework/blob/5.2.x/src/webauthn/src/PublicKeyCredentialCreationOptions.php) classes from webauthn-lib no longer implement the `JsonSerializable` interface. This means that the `jsonSerialize` method is no longer available. Instead, the `Webauthn\PublicKeyCredentialRequestOptions` and `Webauthn\PublicKeyCredentialCreationOptions` classes have been replaced by `LaravelWebauthn\Services\Webauthn\PublicKeyCredentialRequestOptionsRequest` and `LaravelWebauthn\Services\Webauthn\PublicKeyCredentialCreationOptionsRequest` classes, which do implement the `JsonSerializable` interface and help serializing it for Laravel.

The `Webauthn::prepareAssertion` and `Webauthn::prepareAttestation` methods now return `LaravelWebauthn\Services\Webauthn\PublicKeyCredentialRequestOptionsRequest` and `LaravelWebauthn\Services\Webauthn\PublicKeyCredentialCreationOptionsRequest` respectively, and this impacts all classes that rely on it.

This change impacts:
* `LaravelWebauthn\Actions\PrepareAssertionData`
* `LaravelWebauthn\Actions\PrepareCreationData`
* `LaravelWebauthn\Contracts\LoginViewResponse`
* `LaravelWebauthn\Contracts\RegisterViewResponse`
* `LaravelWebauthn\Services\Webauthn\CreationOptionsFactory`
* `LaravelWebauthn\Services\Webauthn\RequestOptionsFactory`
* `LaravelWebauthn\Services\Webauthn`

To implement the change, just replace
```php
use Webauthn\PublicKeyCredentialRequestOptions;
```

with
```php
use LaravelWebauthn\Services\Webauthn\PublicKeyCredentialRequestOptions;
```

and
```php
use Webauthn\PublicKeyCredentialCreationOptions;
```

with
```php
use LaravelWebauthn\Services\Webauthn\PublicKeyCredentialCreationOptions;
```


## User Verification

The user_verification setting was previously forced to `required` when `webauthn.userless` config was set to `preferred` or `required`. It nows only relies on the `webauthn.user_verification` config.


## Simplications

* `EloquentWebAuthnProvider` constructor: removes `validator` parameter
* `OptionsFactory`: removes `repository` parameter
