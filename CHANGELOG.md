## [3.2.1](https://github.com/asbiin/laravel-webauthn/compare/3.2.0...3.2.1) (2022-08-20)


### Bug Fixes

* fix credentialId migration type ([#396](https://github.com/asbiin/laravel-webauthn/issues/396)) ([ea63d70](https://github.com/asbiin/laravel-webauthn/commit/ea63d70bcc2733526fb8e72af5c5511cd301d899))

## [3.2.0](https://github.com/asbiin/laravel-webauthn/compare/3.1.0...3.2.0) (2022-07-30)


### Features

* add WebauthnAuthenticatable trait ([#393](https://github.com/asbiin/laravel-webauthn/issues/393)) ([747df5a](https://github.com/asbiin/laravel-webauthn/commit/747df5ab7bf0f88bbb36f11c0f281464d6c29d0b))

## [3.1.0](https://github.com/asbiin/laravel-webauthn/compare/3.0.1...3.1.0) (2022-07-30)


### Features

* add login event on eloquent provider login ([#392](https://github.com/asbiin/laravel-webauthn/issues/392)) ([16a720e](https://github.com/asbiin/laravel-webauthn/commit/16a720ea15cca233c3c670b1532650d8a1a5d665))

## [3.0.1](https://github.com/asbiin/laravel-webauthn/compare/3.0.0...3.0.1) (2022-06-01)


### Bug Fixes

* json serialize publickey for cache store ([#385](https://github.com/asbiin/laravel-webauthn/issues/385)) ([1b6ad1b](https://github.com/asbiin/laravel-webauthn/commit/1b6ad1b719ae70c850dffaa24d676b2f63b346b2))

# [3.0.0](https://github.com/asbiin/laravel-webauthn/compare/2.0.1...3.0.0) (2022-04-22)


### Bug Fixes

* fix base64 padding ([#381](https://github.com/asbiin/laravel-webauthn/issues/381)) ([6ce3783](https://github.com/asbiin/laravel-webauthn/commit/6ce37830ec89881a75b1d9afffedff57237e92e9))


### Features

* add login without password, and some rewrite ([#361](https://github.com/asbiin/laravel-webauthn/issues/361)) ([6778707](https://github.com/asbiin/laravel-webauthn/commit/6778707b546a9fbd64e5bc0e31091acd1302c749))
* Add support to Laravel 9.x, and remove Laravel 7.x and 8.x ([#377](https://github.com/asbiin/laravel-webauthn/issues/377)) ([bf6c54a](https://github.com/asbiin/laravel-webauthn/commit/bf6c54a27735def337e296cde9de126d131d861f))
* require php 8.1+ ([#378](https://github.com/asbiin/laravel-webauthn/issues/378)) ([af83780](https://github.com/asbiin/laravel-webauthn/commit/af837802ca436d21e15f1b47ca2724ee7b1aaf70))


### BREAKING CHANGES

* options (`webauthn.auth.options` and `webauthn.store.options`) routes are now POST. GET routes are reserved for the views (`webauthn.login` and `webauthn.create`). `webauthn.auth` and `webauthn.store` routes must send data flatten. See details in https://github.com/asbiin/laravel-webauthn/blob/main/docs/migration-v2-to-v3.md .
- `LoginViewResponse` contract has now a `setPublicKey` method
- `RegisterSuccessResponse` contract has now a `setWebauthnKey` method
- `RegisterViewResponse` contract has now a `setPublicKey` method
- `Webauthn::login()` now takes 1 argument for `$user`: `login(\Illuminate\Contracts\Auth\Authenticatable $user)`

## [2.0.1](https://github.com/asbiin/laravel-webauthn/compare/2.0.0...2.0.1) (2022-02-19)


### Bug Fixes

* fix RegisterViewResponse ([#362](https://github.com/asbiin/laravel-webauthn/issues/362)) ([d3a7f9b](https://github.com/asbiin/laravel-webauthn/commit/d3a7f9b9410021b84fd86b86fa77f4d2b41cc15a))

# [2.0.0](https://github.com/asbiin/laravel-webauthn/compare/1.2.0...2.0.0) (2022-02-05)


### Features

* rewrite for more customizable library ([#355](https://github.com/asbiin/laravel-webauthn/issues/355)) ([424caae](https://github.com/asbiin/laravel-webauthn/commit/424caae085bed85781ad7eef8904d644517d02f2))


### BREAKING CHANGES

* This new version is a rewrite with a lot of breaking changes, see details in https://github.com/asbiin/laravel-webauthn/blob/main/docs/migration-v1-to-v2.md

# [1.2.0](https://github.com/asbiin/laravel-webauthn/compare/1.1.0...1.2.0) (2021-12-20)


### Features

* update guzzlehttp/psr7 requirement to || ^2.0 ([#350](https://github.com/asbiin/laravel-webauthn/issues/350)) ([8fe8c8a](https://github.com/asbiin/laravel-webauthn/commit/8fe8c8a77b0967d272a89cf7f8eb5ebed8434a6b))

# [1.1.0](https://github.com/asbiin/laravel-webauthn/compare/1.0.0...1.1.0) (2021-07-03)


### Features

* use host without port for RP ID ([#339](https://github.com/asbiin/laravel-webauthn/issues/339)) ([b957ef8](https://github.com/asbiin/laravel-webauthn/commit/b957ef8d8dd9a0b9a119f1bb97e855bf9f61ac22))

## 1.0.0 — 2020-12-30

 * Major release 1.0.0

## 0.9.1 — 2020-12-30
 ### Fixes:
  * Securing the register route from unchecked users

## 0.9.0 — 2020-09-10
 ### Enhancements:
  * Update dependency for Laravel 8
  * Remove composer.lock
  * Update README doc

## 0.8.0 — 2020-03-07
 ### New features:
  * Add French and German languages

 ### Enhancements:
  * Support Laravel 7 and higher
  * Upgrade to WebAuthn v3.0

## 0.7.0 — 2019-12-05
 ### New features:
  * Make the callback url setable by the user before the middleware hit

## 0.6.4 — 2019-11-16
 ### Enhancements:
  * Add suggestions for web-token/jwt-signature-algorithm-* dependencies

 ### Fixes:
  * Fix Uuid decode

## 0.6.3 — 2019-10-12
 ### Enhancements:
  * Upgrade to Laravel 6.x

## 0.6.2 — 2019-10-08
 ### Fixes:
  * Fix javascript base64url decode function

## 0.6.1 — 2019-09-20
 ### Fixes:
  * Fix aaguid parsing

## 0.6.0 — 2019-09-19
 ### Enhancements:
  * Upgrade to webauthn-lib v2.x

 ### Fixes:
  * Use bigInteger for foreign key reference

## 0.5.1 — 2019-05-20
 ### Fixes:
  * Fix google safetynet config test
  * Remove Guzzle6 direct dependency

## 0.5.0 — 2019-05-03
 ### Enhancements:
  * Name the destroy route
  * Add Google Safetynet Apikey config

 ### Fixes:
  * Remove use of empty() php function
  * Remove deprecated methods

## 0.4.1 — 2019-04-18
 ### Fixes:
  * Fix javascript resource in case no key is registered yet

## 0.4.0 — 2019-04-18
 ### New features:
  * Add resources files to client adoption
  * Add register and auth example pages

 ### Enhancements:
  * Add callback URL in session

 ### Fixes:
  * Use safe php functions
  * Fix redirect guest callback

## 0.3.0 — 2019-04-08
 ### New features:
  * Add WebauthnLoginData event
  * Add new redirects

 ### Fixes:
  * Fix Webauthn Facade

## 0.2.0 — 2019-04-06
 ### Enhancements:
  * Remove userHandle in model
  * Return a resource after registering
  * Update to web-auth v1.1.0

 ### Fixes:
  * Fix some contract bindings
  * Split services to use Deferred services

## 0.1.0 — 2019-04-04
 ### New features:
  * First release
