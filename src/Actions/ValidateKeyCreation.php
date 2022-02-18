<?php

namespace LaravelWebauthn\Actions;

use Exception;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use LaravelWebauthn\Events\WebauthnRegisterFailed;
use LaravelWebauthn\Facades\Webauthn;
use LaravelWebauthn\Models\WebauthnKey;

class ValidateKeyCreation
{
    /**
     * Register a new key.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  array  $data
     * @param  string  $keyName
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function __invoke(Authenticatable $user, array $data, string $keyName): ?Model
    {
        if (! Webauthn::canRegister($user)) {
            $this->throwFailedRegisterException($user);
        }

        try {
            $webauthnKey = Webauthn::validateAttestation($user, $data, $keyName);

            // Login the user immediately.
            Webauthn::login($user);

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
            Webauthn::username() => [trans('webauthn::errors.wrong_validation')],
        ]);
    }
}
