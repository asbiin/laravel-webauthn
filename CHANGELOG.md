# UNRELEASED CHANGES:

 ### New features:
  *

 ### Enhancements:
  *

 ### Fixes:
  *


# RELEASED VERSIONS:
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
