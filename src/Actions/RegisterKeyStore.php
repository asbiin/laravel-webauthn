<?php

namespace LaravelWebauthn\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Validation\ValidationException;
use LaravelWebauthn\Events\WebauthnRegister;
use LaravelWebauthn\Events\WebauthnRegisterFailed;
use LaravelWebauthn\Facades\Webauthn;
use LaravelWebauthn\Services\Webauthn\PublicKeyCredentialValidator;
use Webauthn\PublicKeyCredentialCreationOptions;
use LaravelWebauthn\Models\WebauthnKey;

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

            Webauthn::forceAuthenticate();

            return $webauthnKey;
        } catch (\Exception $e) {
            $this->throwFailedRegisterException($user);
        }

        return null;
    }

    /**
     * Throw a failed register validation exception.
     *
     * @param  Authenticatable  $user
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function throwFailedRegisterException($user)
    {
        WebauthnRegisterFailed::dispatch($user);

        throw ValidationException::withMessages([
            'register' => [trans('webauthn::errors.cannot_register_new_key')],
        ]);
    }
}
