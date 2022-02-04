<?php

namespace LaravelWebauthn\Actions;

use Exception;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Validation\ValidationException;
use LaravelWebauthn\Events\WebauthnRegister;
use LaravelWebauthn\Events\WebauthnRegisterFailed;
use LaravelWebauthn\Facades\Webauthn;
use LaravelWebauthn\Models\WebauthnKey;
use LaravelWebauthn\Services\Webauthn\CredentialAttestationValidator;
use Webauthn\PublicKeyCredentialCreationOptions;
use Illuminate\Contracts\Foundation\Application;

class RegisterKeyStore
{
    /**
     * The Illuminate application instance.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * Create a new action.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Register a new key.
     *
     * @param  Authenticatable  $user
     * @param  PublicKeyCredentialCreationOptions  $publicKey
     * @param  string  $data
     * @param  string  $keyName
     * @return WebauthnKey|null
     */
    public function __invoke(Authenticatable $user, PublicKeyCredentialCreationOptions $publicKey, string $data, string $keyName): ?WebauthnKey
    {
        if (! Webauthn::canRegister($user)) {
            $this->throwFailedRegisterException($user);
        }

        try {
            $publicKeyCredentialSource = $this->app[CredentialAttestationValidator::class]($publicKey, $data);

            $webauthnKey = Webauthn::create($user, $keyName, $publicKeyCredentialSource);

            WebauthnRegister::dispatch($webauthnKey);

            Webauthn::login();

            return $webauthnKey;
        } catch (Exception $e) {
            $this->throwFailedRegisterException($user, $e);
        }

        return null;
    }

    /**
     * Throw a failed register validation exception.
     *
     * @param  Authenticatable  $user
     * @param  Exception|null  $e
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function throwFailedRegisterException(Authenticatable $user, ?Exception $e = null)
    {
        WebauthnRegisterFailed::dispatch($user, $e);

        throw ValidationException::withMessages([
            'register' => [trans('webauthn::errors.cannot_register_new_key')],
        ]);
    }
}
