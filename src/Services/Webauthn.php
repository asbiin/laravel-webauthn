<?php

namespace LaravelWebauthn\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use LaravelWebauthn\Events\WebauthnLogin;
use LaravelWebauthn\Events\WebauthnLoginData;
use LaravelWebauthn\Events\WebauthnRegister;
use LaravelWebauthn\Events\WebauthnRegisterData;
use LaravelWebauthn\Models\WebauthnKey;
use LaravelWebauthn\Services\Webauthn\CreationOptionsFactory;
use LaravelWebauthn\Services\Webauthn\CredentialAssertionValidator;
use LaravelWebauthn\Services\Webauthn\CredentialAttestationValidator;
use LaravelWebauthn\Services\Webauthn\RequestOptionsFactory;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRequestOptions;

class Webauthn extends WebauthnRepository
{
    /**
     * The callback that is responsible for building the authentication pipeline array, if applicable.
     *
     * @var callable|null
     */
    public static $authenticateThroughCallback;

    /**
     * The callback that is responsible for validating authentication credentials, if applicable.
     *
     * @var callable|null
     */
    public static $authenticateUsingCallback;

    /**
     * Indicates if Webauthn routes will be registered.
     *
     * @var bool
     */
    public static bool $registersRoutes = true;

    final public function __construct()
    {
    }

    /**
     * Get the username used for authentication.
     *
     * @return string
     */
    public static function username()
    {
        return config('webauthn.username', 'email');
    }

    /**
     * Get a completion redirect path for a specific feature.
     *
     * @param  string  $redirect
     * @return string
     */
    public static function redirects(string $redirect, $default = null)
    {
        return config('webauthn.redirects.'.$redirect) ?? $default ?? config('webauthn.home');
    }

    /**
     * Save authentication in session.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $user
     * @return void
     */
    public static function login(?Authenticatable $user)
    {
        session([static::sessionName() => true]);

        if ($user !== null) {
            WebauthnLogin::dispatch($user);
        }
    }

    /**
     * Remove authentication from session.
     *
     * @return void
     */
    public static function logout()
    {
        session()->forget(static::sessionName());
    }

    /**
     * Force authentication in session.
     *
     * @return void
     *
     * @deprecated use login() instead
     */
    public static function forceAuthenticate()
    {
        static::login(null);
    }

    /**
     * Force remove authentication in session.
     *
     * @return void
     *
     * @deprecated use logout() instead
     */
    public static function forgetAuthenticate()
    {
        static::logout();
    }

    /**
     * Get publicKey data to prepare Webauthn login.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return \Webauthn\PublicKeyCredentialRequestOptions
     */
    public static function prepareAssertion(Authenticatable $user): PublicKeyCredentialRequestOptions
    {
        return tap(app(RequestOptionsFactory::class)($user), function ($publicKey) use ($user) {
            WebauthnLoginData::dispatch($user, $publicKey);
        });
    }

    /**
     * Validate a Webauthn login request.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  array  $credentials
     * @return bool
     */
    public static function validateAssertion(Authenticatable $user, array $credentials): bool
    {
        return app(CredentialAssertionValidator::class)($user, $credentials);
    }

    /**
     * Get publicKey data to prepare Webauthn key creation.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return \Webauthn\PublicKeyCredentialCreationOptions
     */
    public static function prepareAttestation(Authenticatable $user): PublicKeyCredentialCreationOptions
    {
        return tap(app(CreationOptionsFactory::class)($user), function ($publicKey) use ($user) {
            WebauthnRegisterData::dispatch($user, $publicKey);
        });
    }

    /**
     * Validate a Webauthn key creation request.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  array  $credentials
     * @param  string  $keyName
     * @return \LaravelWebauthn\Models\WebauthnKey
     */
    public static function validateAttestation(Authenticatable $user, array $credentials, string $keyName): WebauthnKey
    {
        $publicKey = app(CredentialAttestationValidator::class)($user, $credentials);

        return tap(static::create($user, $keyName, $publicKey), function ($webauthnKey) {
            WebauthnRegister::dispatch($webauthnKey);
        });
    }

    /**
     * Check authentication of the user in session.
     *
     * @return bool
     */
    public static function check(): bool
    {
        return (bool) session(static::sessionName(), false);
    }

    /**
     * Get webauthn session store name.
     *
     * @return string
     */
    private static function sessionName(): string
    {
        return config('webauthn.session_name', config('webauthn.sessionName', 'webauthn_auth'));
    }

    /**
     * Test if the user has one or more webauthn key.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return bool
     */
    public static function enabled(Authenticatable $user): bool
    {
        return static::webauthnEnabled() && static::hasKey($user);
    }

    /**
     * Test if the user can register a new key.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return bool
     */
    public static function canRegister(Authenticatable $user): bool
    {
        return static::webauthnEnabled() && (! static::enabled($user) || static::check());
    }

    /**
     * Test if webauthn is enabled.
     *
     * @return bool
     */
    public static function webauthnEnabled(): bool
    {
        return (bool) config('webauthn.enable', true);
    }

    /**
     * Register a callback that is responsible for building the authentication pipeline array.
     *
     * @param  callable  $callback
     * @return void
     */
    public static function authenticateThrough(callable $callback)
    {
        static::$authenticateThroughCallback = $callback;
    }

    /**
     * Register a callback that is responsible for validating incoming authentication credentials.
     *
     * @param  callable  $callback
     * @return void
     */
    public static function authenticateUsing(callable $callback)
    {
        static::$authenticateUsingCallback = $callback;
    }

    /**
     * Register a class / callback that should be used to the destroy view response.
     *
     * @param  string  $callback
     * @return void
     * @codeCoverageIgnore
     */
    public static function destroyViewResponseUsing(string $callback)
    {
        app()->singleton(\LaravelWebauthn\Contracts\DestroyResponse::class, $callback);
    }

    /**
     * Register a class / callback that should be used to the update view response.
     *
     * @param  string  $callback
     * @return void
     * @codeCoverageIgnore
     */
    public static function updateViewResponseUsing(string $callback)
    {
        app()->singleton(\LaravelWebauthn\Contracts\UpdateResponse::class, $callback);
    }

    /**
     * Register a class / callback that should be used to the login success view response.
     *
     * @param  string  $callback
     * @return void
     * @codeCoverageIgnore
     */
    public static function loginSuccessResponseUsing(string $callback)
    {
        app()->singleton(\LaravelWebauthn\Contracts\LoginSuccessResponse::class, $callback);
    }

    /**
     * Register a class / callback that should be used to the login view response.
     *
     * @param  string  $callback
     * @return void
     * @codeCoverageIgnore
     */
    public static function loginViewResponseUsing(string $callback)
    {
        app()->singleton(\LaravelWebauthn\Contracts\LoginViewResponse::class, $callback);
    }

    /**
     * Register a class / callback that should be used to the register key success view response.
     *
     * @param  string  $callback
     * @return void
     * @codeCoverageIgnore
     */
    public static function registerSuccessResponseUsing(string $callback)
    {
        app()->singleton(\LaravelWebauthn\Contracts\RegisterSuccessResponse::class, $callback);
    }

    /**
     * Register a class / callback that should be used to the register creation view response.
     *
     * @param  string  $callback
     * @return void
     * @codeCoverageIgnore
     */
    public static function registerViewResponseUsing(string $callback)
    {
        app()->singleton(\LaravelWebauthn\Contracts\RegisterViewResponse::class, $callback);
    }

    /**
     * Configure Webauthn to not register its routes.
     *
     * @return self
     */
    public static function ignoreRoutes(): self
    {
        static::$registersRoutes = false;

        return new static;
    }
}
