# Migration from v2 to v3  <!-- omit in toc -->

- [Routes](#routes)
  - [Views](#views)
  - [Options](#options)
- [Contracts](#contracts)
- [Config file](#config-file)
  - [Facade](#facade)

V3 of Webauthn introduces multiple breaking changes:

## Routes

The signature of the Webauthn login and registration routes has changed.

| Request | Route | Description |
|---------|-------|-------------|
| POST `/webauthn/auth` | `webauthn.auth` | Post data after a WebAuthn login validate. |
| POST `/webauthn/keys` | `webauthn.store` | Post data after a WebAuthn register check. |

On v1/v2, these routes contained a `data` or `register` parameter, to sent the webauthn signature data after a login or registration validation.

**v1 & v2**
```js
 webauthn.sign(
      publicKey,
      function (data) {
          /* Send 'data' to validate login */
          axios.post(route('webauthn.auth'), {
              data: JSON.stringify(data),
              remember: this.remember ? 'on' : ''
          }).then( /* ... */ );
      }
    );
```

The content of `data` must now be sent directly to the `webauthn.auth` or `webauthn.store` request

**v3**
```js
 webauthn.sign(
      publicKey,
      function (data) {
          /* Send 'data' to validate login */
          axios.post(route('webauthn.auth'), {
              ...data,
              remember: this.remember ? 'on' : ''
          }).then( /* ... */ );
      }
    );
```

This way, it's now possible to use `https://github.com/web-auth/webauthn-helper` javascript helper.


### Views

GET `webauthn.create` and GET `webauthn.login` routes **always return the view page**.
They can now be disabled by setting the `views` configuration value within your application's `config/webauthn.php` configuration file to false:

```php
'views' => false,
```

### Options

There are 2 new routes:

| Request                      | Route          | Description       |
|------------------------------|-------------------|-----------------|
| POST `/webauthn/auth/options` | `webauthn.auth.options` | Get the publicKey and challenge to initiate a WebAuthn login. |
| POST `/webauthn/keys/options` | `webauthn.store.options` | Get the publicKeys and challenge to initiate a WebAuthn registration. |

These are available to initiate a Webauthn login or registration process. When using GET `webauthn.create` and GET `webauthn.login`, the render page already has a `publicKey` parameter with the challenge key. If you only need to create the challenge, you can use these routes instead.


## Contracts

Some of the [contracts](/src/Contracts) have changed:

- `LoginViewResponse` contract has now a `setPublicKey` method.
- `RegisterSuccessResponse` contract has now a `setWebauthnKey` method.
- `RegisterViewResponse` contract has now a `setPublicKey` method.


## Config file

`config/webauthn.php` file has new parameters.

The `sessionName` parameter has been renamed to `session_name`.

You can re-publish the config file with
```console
php artisan vendor:publish --provider="LaravelWebauthn\WebauthnServiceProvider"
```


### Facade

The `LaravelWebauthn\Facades\Webauthn` facade has changed:

The `login` method signature has changed, it now accept a `$user` parameter:

```php
login(\Illuminate\Contracts\Auth\Authenticatable $user): void
```

