<?php

namespace LaravelWebauthn\Actions;

use Exception;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Validation\ValidationException;
use LaravelWebauthn\Events\WebauthnLogin;
use LaravelWebauthn\Events\WebauthnLoginFailed;
use LaravelWebauthn\Facades\Webauthn;
use LaravelWebauthn\Services\Webauthn\PublicKeyCredentialValidator;
use Webauthn\PublicKeyCredentialRequestOptions;

class LoginAttempt
{
    /**
     * Authenticate a user.
     *
     * @param  Authenticatable  $user
     * @param  PublicKeyCredentialRequestOptions  $publicKey
     * @param  string  $data
     * @return bool
     */
    public function __invoke(Authenticatable $user, PublicKeyCredentialRequestOptions $publicKey, string $data): bool
    {
        try {
            $result = app(PublicKeyCredentialValidator::class)
                ->check($user, $publicKey, $data);

            if ($result === true) {
                Webauthn::forceAuthenticate();

                WebauthnLogin::dispatch($user);

                return true;
            }
        } catch (\Exception $e) {
            $this->throwFailedLoginException($user, $e);
        }

        return false;
    }

    /**
     * Throw a failed login validation exception.
     *
     * @param  Authenticatable  $user
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function throwFailedLoginException(Authenticatable $user, Exception $e)
    {
        WebauthnLoginFailed::dispatch($user);

        throw ValidationException::withMessages([
            'data' => [trans('webauthn::errors.login_failed')],
        ]);
    }
}
