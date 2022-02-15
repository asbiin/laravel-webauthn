<?php

// namespace LaravelWebauthn\Tests\Fake;

// use Illuminate\Contracts\Auth\Authenticatable as User;
// use Illuminate\Contracts\Auth\Authenticatable;
// use LaravelWebauthn\Events\WebauthnLoginData;
// use LaravelWebauthn\Models\WebauthnKey;
// use LaravelWebauthn\Services\Webauthn\RequestOptionsFactory;
// use LaravelWebauthn\Services\WebauthnRepository;
// use Webauthn\PublicKeyCredentialRequestOptions;

// class FakeWebauthn extends WebauthnRepository
// {
//     protected $authenticate = true;

//     public static function redirects(string $redirect, $default = null)
//     {
//         return config('webauthn.redirects.'.$redirect) ?? $default ?? config('webauthn.home');
//     }

//     public function setAuthenticate(bool $authenticate)
//     {
//         $this->authenticate = $authenticate;
//     }

//     public static function login()
//     {
//         session([static::sessionName() => true]);
//     }

//     public static function logout()
//     {
//         session()->forget(static::sessionName());
//     }

//     private static function sessionName(): string
//     {
//         return config('webauthn.sessionName');
//     }

//     public static function check(): bool
//     {
//         return (bool) session(static::sessionName(), false);
//     }

//     public static function enabled(User $user): bool
//     {
//         return config('webauthn.enable') &&
//             WebauthnKey::where('user_id', $user->getAuthIdentifier())->count() > 0;
//     }

//     public static function canRegister(User $user): bool
//     {
//         return (bool) ! static::enabled($user) || static::check();
//     }

//     public static function hasKey(User $user): bool
//     {
//         return WebauthnKey::where('user_id', $user->getAuthIdentifier())->count() > 0;
//     }

//     /**
//      * Get publicKey data to prepare Webauthn login.
//      *
//      * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
//      * @return \Webauthn\PublicKeyCredentialRequestOptions
//      */
//     public static function prepareAssertion(Authenticatable $user): PublicKeyCredentialRequestOptions
//     {
//         return tap(app(RequestOptionsFactory::class)($user), function ($publicKey) use ($user) {
//             WebauthnLoginData::dispatch($user, $publicKey);
//         });
//     }
//     /**
//      * Get publicKey data to prepare Webauthn key creation.
//      *
//      * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
//      * @return \Webauthn\PublicKeyCredentialCreationOptions
//      */
//     public static function prepareAttestation(Authenticatable $user): PublicKeyCredentialCreationOptions
//     {
//         return tap(app(CreationOptionsFactory::class)($user), function ($publicKey) use ($user) {
//             WebauthnRegisterData::dispatch($user, $publicKey);
//         });
//     }
// }
