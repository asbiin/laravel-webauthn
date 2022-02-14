# Migration from v1 to v2  <!-- omit in toc -->

- [Routes](#routes)
- [Config file](#config-file)
  - [Navigation](#navigation)
  - [Facade](#facade)
- [Dependencies](#dependencies)
  - [Laravel](#laravel)
  - [PSR-7](#psr-7)

V2 of Webauthn introduces multiple breaking changes:


## Routes

| Request                      | Route v1          | Route v2        |
|------------------------------|-------------------|-----------------|
| GET `/webauthn/register`     | webauthn.register | webauthn.create |
| POST `/webauthn/register`    | webauthn.create   | webauthn.store  |
| UPDATE `/webauthn/keys/{id}` |                   | webauthn.update |

Other routes are not modified:
- GET `/webauthn/auth` / `route('webauthn.login')`
- POST `/webauthn/auth` / `route('webauthn.auth')`
- DELETE `/webauthn/keys/{id}` / `route('webauthn.destroy')`


## Config file

`config/webauthn.php` file structure has changed.

You should re-publish it with
```console
php artisan vendor:publish --provider="LaravelWebauthn\WebauthnServiceProvider"
```

### Navigation

`authenticate` and `register` arrays are not used anymore.

To define how navigation is handled after a success login or register key, you can now set the `redirects` array:

```php
    'redirects' => [
        'login' => null,
        'register' => null,
    ];
```

You can define here the urls to redirect to after a success login or register key.
Note that redirects are not used in case of application/json requests.


### Facade

`LaravelWebauthn\Facades\Webauthn` facade has changed.

* Removed methods:
    - getRegisterData(\Illuminate\Contracts\Auth\Authenticatable $user)
    - doRegister(\Illuminate\Contracts\Auth\Authenticatable $user, PublicKeyCredentialCreationOptions $publicKey, string $data, string $keyName)
    - getAuthenticateData(\Illuminate\Contracts\Auth\Authenticatable $user)
    - doAuthenticate(\Illuminate\Contracts\Auth\Authenticatable $user, PublicKeyCredentialRequestOptions $publicKey, string $data)
    - forceAuthenticate()

* New methods:
    - create(\Illuminate\Contracts\Auth\Authenticatable $user, string $keyName, \Webauthn\PublicKeyCredentialSource $publicKeyCredentialSource)
    - login()
    - logout()
    - webauthnEnabled()
    - hasKey(\Illuminate\Contracts\Auth\Authenticatable $user)


### Keeping Existing Keys

If your application has users with existing Webauthn Keys then you will need to update the encoding of the `credentialId` column in the `webauthn_keys` table from base64 to base64URL, otherwise their key will not be found when they attempt to authenticate. This is because the casting has been updated for the `WebauthnKey` model.

A simple migration that retrieves and updates the encoding is shown below:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use LaravelWebauthn\Models\WebauthnKey;

class UpdateCredentialIdValueInWebauthnKeysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        WebauthnKey::select(['id', 'credentialId'])->chunk(200, function ($keys) {
            foreach ($keys as $key) {
                $key->update(['credentialId' => $key->credentialId]);
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        WebauthnKey::select(['id', 'credentialId'])->chunk(200, function ($keys) {
            foreach ($keys as $key) {
                $key->setRawAttributes(['credentialId' => base64_encode($key->credentialId)]);
                $key->save();
            }
        });
    }
}
```


## Dependencies

### Laravel

v2 requires Laravel 7 or later.

### PSR-7

`guzzlehttp/psr7` is no longer a required dependency.
However, you will need a `psr/http-factory-implementation` implementation, like `guzzlehttp/psr7`.
