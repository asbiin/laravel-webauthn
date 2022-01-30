<?php

namespace LaravelWebauthn\Actions;

use Exception;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Validation\ValidationException;
use LaravelWebauthn\Events\WebauthnRegister;
use LaravelWebauthn\Events\WebauthnRegisterFailed;
use LaravelWebauthn\Facades\Webauthn;
use LaravelWebauthn\Models\WebauthnKey;
use LaravelWebauthn\Services\Webauthn\PublicKeyCredentialValidator;
use Webauthn\PublicKeyCredentialCreationOptions;

class RegisterKeyStore
{
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
            $publicKeyCredentialSource = app(PublicKeyCredentialValidator::class)
                ->validate($publicKey, $data);

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
