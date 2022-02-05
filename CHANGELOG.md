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
