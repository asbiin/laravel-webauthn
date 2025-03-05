# Migration from v4 to v5

V5 of Webauthn introduces multiple breaking changes:

## Classes and Interfaces

In every return type and parameter, these changes have been made:
* `Webauthn\PublicKeyCredentialRequestOptions` class is been replaced by`LaravelWebauthn\Services\Webauthn\PublicKeyCredentialRequestOptionsRequest`
* `Webauthn\PublicKeyCredentialCreationOptions` class is been replaced `LaravelWebauthn\Services\Webauthn\PublicKeyCredentialCreationOptionsRequest`.

This applies to
* `LaravelWebauthn\Actions\PrepareAssertionData`
* `LaravelWebauthn\Actions\PrepareCreationData`
* `LaravelWebauthn\Contracts\LoginViewResponse`
* `LaravelWebauthn\Contracts\RegisterViewResponse`
* `LaravelWebauthn\Services\Webauthn\CreationOptionsFactory`
* `LaravelWebauthn\Services\Webauthn\RequestOptionsFactory`
* `LaravelWebauthn\Services\Webauthn`


## User Verification

The user_verification setting was previously forced to `required` when `webauthn.userless` config was set to `preferred` or `required`. It nows only relies on the `webauthn.user_verification` config.


## Simplications

* `EloquentWebAuthnProvider` constructor: removes `validator` parameter
* `OptionsFactory`: removes `repository` parameter
