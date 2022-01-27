<?php

namespace LaravelWebauthn\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Validation\ValidationException;
use LaravelWebauthn\Events\WebauthnRegisterData;
use LaravelWebauthn\Events\WebauthnRegisterFailed;
use LaravelWebauthn\Facades\Webauthn;
use LaravelWebauthn\Services\Webauthn\PublicKeyCredentialCreationOptionsFactory;
use Webauthn\PublicKeyCredentialCreationOptions;

class RegisterKeyPrepare
{
    /**
     * Get data to register a new key.
     *
     * @param  Authenticatable  $user
     * @return PublicKeyCredentialCreationOptions
     */
    public function __invoke(Authenticatable $user): PublicKeyCredentialCreationOptions
    {
        if (! Webauthn::canRegister($user)) {
            $this->throwFailedRegisterException($user);
        }

        $publicKey = app(PublicKeyCredentialCreationOptionsFactory::class)($user);

        WebauthnRegisterData::dispatch($user, $publicKey);

        return $publicKey;
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
