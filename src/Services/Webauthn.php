<?php

namespace LaravelWebauthn\Services;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use LaravelWebauthn\Events\WebauthnLogin;
use LaravelWebauthn\Events\WebauthnLoginData;
use LaravelWebauthn\Events\WebauthnRegister;
use LaravelWebauthn\Events\WebauthnRegisterData;
use LaravelWebauthn\Services\Webauthn\CreationOptionsFactory;
use LaravelWebauthn\Services\Webauthn\CredentialAssertionValidator;
use LaravelWebauthn\Services\Webauthn\CredentialAttestationValidator;
use LaravelWebauthn\Services\Webauthn\RequestOptionsFactory;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\Util\Base64;

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
     */
    public static bool $registersRoutes = true;

    /**
     * Get the username used for authentication.
     */
    public static function username(): string
    {
        return config('webauthn.username', 'email');
    }

    /**
     * Get a completion redirect path for a specific feature.
     */
    public static function redirects(string $redirect, string $default = null): string
    {
        return config('webauthn.redirects.'.$redirect) ?? $default ?? config('webauthn.home');
    }

    /**
     * Save authentication in session.
     */
    public static function login(?User $user): void
    {
        session([static::sessionName() => true]);

        if ($user !== null) {
            WebauthnLogin::dispatch($user);
        }
    }

    /**
     * Remove authentication from session.
     */
    public static function logout(): void
    {
        session()->forget(static::sessionName());
    }

    /**
     * Force authentication in session.
     *
     * @deprecated use login() instead
     *
     * @codeCoverageIgnore
     */
    public static function forceAuthenticate(): void
    {
        static::login(null);
    }

    /**
     * Force remove authentication in session.
     *
     * @deprecated use logout() instead
     *
     * @codeCoverageIgnore
     */
    public static function forgetAuthenticate(): void
    {
        static::logout();
    }

    /**
     * Get publicKey data to prepare Webauthn login.
     */
    public static function prepareAssertion(User $user): PublicKeyCredentialRequestOptions
    {
        return tap(app(RequestOptionsFactory::class)($user), function ($publicKey) use ($user) {
            WebauthnLoginData::dispatch($user, $publicKey);
        });
    }

    /**
     * Validate a Webauthn login request.
     */
    public static function validateAssertion(User $user, array $credentials): bool
    {
        if (($authenticatorData = Arr::get($credentials, 'response.authenticatorData')) !== null) {
            Arr::set($credentials, 'response.authenticatorData', Base64UrlSafe::encodeUnpadded(Base64::decode($authenticatorData)));
        }

        return app(CredentialAssertionValidator::class)($user, $credentials);
    }

    /**
     * Get publicKey data to prepare Webauthn key creation.
     */
    public static function prepareAttestation(User $user): PublicKeyCredentialCreationOptions
    {
        return tap(app(CreationOptionsFactory::class)($user), function ($publicKey) use ($user) {
            WebauthnRegisterData::dispatch($user, $publicKey);
        });
    }

    /**
     * Validate a Webauthn key creation request.
     */
    public static function validateAttestation(User $user, array $credentials, string $keyName): Model
    {
        if (($clientDataJSON = Arr::get($credentials, 'response.clientDataJSON')) !== null) {
            Arr::set($credentials, 'response.clientDataJSON', Base64UrlSafe::encodeUnpadded(Base64::decode($clientDataJSON)));
        }

        $publicKey = app(CredentialAttestationValidator::class)($user, $credentials);

        return tap(static::create($user, $keyName, $publicKey), function ($webauthnKey) {
            WebauthnRegister::dispatch($webauthnKey);
        });
    }

    /**
     * Check authentication of the user in session.
     */
    public static function check(): bool
    {
        return (bool) session(static::sessionName(), false);
    }

    /**
     * Get webauthn session store name.
     */
    public static function sessionName(): string
    {
        return config('webauthn.session_name', config('webauthn.sessionName', 'webauthn_auth'));
    }

    /**
     * Test if the user has one or more webauthn key.
     */
    public static function enabled(User $user): bool
    {
        return static::webauthnEnabled() && static::hasKey($user);
    }

    /**
     * Test if the user can register a new key.
     */
    public static function canRegister(User $user): bool
    {
        return static::webauthnEnabled() && (! static::enabled($user) || static::check());
    }

    /**
     * Test if webauthn is enabled.
     */
    public static function webauthnEnabled(): bool
    {
        return (bool) config('webauthn.enable', true);
    }

    /**
     * Register a callback that is responsible for building the authentication pipeline array.
     *
     * @codeCoverageIgnore
     */
    public static function authenticateThrough(callable $callback): void
    {
        static::$authenticateThroughCallback = $callback;
    }

    /**
     * Register a callback that is responsible for validating incoming authentication credentials.
     */
    public static function authenticateUsing(callable $callback): void
    {
        static::$authenticateUsingCallback = $callback;
    }

    /**
     * Register a class / callback that should be used to the destroy view response.
     *
     * @codeCoverageIgnore
     */
    public static function destroyViewResponseUsing(\Closure|string $callback): void
    {
        app()->singleton(\LaravelWebauthn\Contracts\DestroyResponse::class, $callback);
    }

    /**
     * Register a class / callback that should be used to the update view response.
     *
     * @codeCoverageIgnore
     */
    public static function updateViewResponseUsing(\Closure|string $callback): void
    {
        app()->singleton(\LaravelWebauthn\Contracts\UpdateResponse::class, $callback);
    }

    /**
     * Register a class / callback that should be used to the login success view response.
     *
     * @codeCoverageIgnore
     */
    public static function loginSuccessResponseUsing(\Closure|string $callback): void
    {
        app()->singleton(\LaravelWebauthn\Contracts\LoginSuccessResponse::class, $callback);
    }

    /**
     * Register a class / callback that should be used to the login view response.
     *
     * @codeCoverageIgnore
     */
    public static function loginViewResponseUsing(\Closure|string $callback): void
    {
        app()->singleton(\LaravelWebauthn\Contracts\LoginViewResponse::class, $callback);
    }

    /**
     * Register a class / callback that should be used to the register key success view response.
     *
     * @codeCoverageIgnore
     */
    public static function registerSuccessResponseUsing(\Closure|string $callback): void
    {
        app()->singleton(\LaravelWebauthn\Contracts\RegisterSuccessResponse::class, $callback);
    }

    /**
     * Register a class / callback that should be used to the register creation view response.
     *
     * @codeCoverageIgnore
     */
    public static function registerViewResponseUsing(\Closure|string $callback): void
    {
        app()->singleton(\LaravelWebauthn\Contracts\RegisterViewResponse::class, $callback);
    }

    /**
     * Configure Webauthn to not register its routes.
     */
    public static function ignoreRoutes(): void
    {
        static::$registersRoutes = false;
    }
}
