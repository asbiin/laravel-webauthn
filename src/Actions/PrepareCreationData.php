<?php

namespace LaravelWebauthn\Actions;

use Exception;
use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Validation\ValidationException;
use LaravelWebauthn\Events\WebauthnRegisterFailed;
use LaravelWebauthn\Facades\Webauthn;
use Webauthn\PublicKeyCredentialCreationOptions;

class PrepareCreationData
{
    /**
     * Get data to register a new key.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return PublicKeyCredentialCreationOptions
     */
    public function __invoke(User $user): PublicKeyCredentialCreationOptions
    {
        if (! Webauthn::canRegister($user)) {
            $this->throwFailedRegisterException($user);
        }

        return Webauthn::prepareAttestation($user);
    }

    /**
     * Throw a failed register validation exception.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  Exception|null  $e
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function throwFailedRegisterException(User $user, ?Exception $e = null)
    {
        WebauthnRegisterFailed::dispatch($user, $e);

        throw ValidationException::withMessages([
            Webauthn::username() => [trans('webauthn::errors.cannot_register_new_key')],
        ]);
    }
}
