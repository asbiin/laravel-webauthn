<?php

namespace LaravelWebauthn\Actions;

use Exception;
use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use LaravelWebauthn\Events\WebauthnRegisterFailed;
use LaravelWebauthn\Facades\Webauthn;

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
    public function __invoke(User $user, array $data, string $keyName): ?Model
    {
        if (! Webauthn::canRegister($user)) {
            $this->throwFailedRegisterException($user);
        }

        return tap($this->validateAttestation($user, $data, $keyName), function () use ($user) {
            // Login the user immediately.
            Webauthn::login($user);
        });
    }

    /**
     * Validate key.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  array  $data
     * @param  string  $keyName
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    protected function validateAttestation(User $user, array $data, string $keyName): ?Model
    {
        try {
            return Webauthn::validateAttestation($user, $data, $keyName);
        } catch (Exception $e) {
            $this->throwFailedRegisterException($user, $e);
        }

        return null; // @codeCoverageIgnore
    }

    /**
     * Throw a failed register validation exception.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  \Exception|null  $e
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function throwFailedRegisterException(User $user, ?Exception $e = null)
    {
        WebauthnRegisterFailed::dispatch($user, $e);

        throw ValidationException::withMessages([
            Webauthn::username() => [trans('webauthn::errors.wrong_validation')],
        ]);
    }
}
