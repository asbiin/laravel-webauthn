<?php

namespace LaravelWebauthn\Actions;

use Exception;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Validation\ValidationException;
use LaravelWebauthn\Events\WebauthnLogin;
use LaravelWebauthn\Events\WebauthnLoginFailed;
use LaravelWebauthn\Facades\Webauthn;
use LaravelWebauthn\Services\Webauthn\CredentialAssertionValidator;
use Webauthn\PublicKeyCredentialRequestOptions;

class LoginAttempt
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
            $result = $this->app[CredentialAssertionValidator::class]($user, $publicKey, $data);

            if ($result === true) {
                Webauthn::login();

                WebauthnLogin::dispatch($user);

                return true;
            }
        } catch (Exception $e) {
            $this->throwFailedLoginException($user, $e);
        }

        return false;
    }

    /**
     * Throw a failed login validation exception.
     *
     * @param  Authenticatable  $user
     * @param  Exception|null  $e
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function throwFailedLoginException(Authenticatable $user, ?Exception $e = null)
    {
        WebauthnLoginFailed::dispatch($user, $e);

        throw ValidationException::withMessages([
            'data' => [trans('webauthn::errors.login_failed')],
        ]);
    }
}
