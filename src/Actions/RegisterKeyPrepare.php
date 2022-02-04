<?php

namespace LaravelWebauthn\Actions;

use Exception;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Validation\ValidationException;
use LaravelWebauthn\Events\WebauthnRegisterData;
use LaravelWebauthn\Events\WebauthnRegisterFailed;
use LaravelWebauthn\Facades\Webauthn;
use LaravelWebauthn\Services\Webauthn\CreationOptionsFactory;
use Webauthn\PublicKeyCredentialCreationOptions;

class RegisterKeyPrepare
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

        $publicKey = $this->app[CreationOptionsFactory::class]($user);

        WebauthnRegisterData::dispatch($user, $publicKey);

        return $publicKey;
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
    protected function throwFailedRegisterException($user, ?Exception $e = null)
    {
        WebauthnRegisterFailed::dispatch($user, $e);

        throw ValidationException::withMessages([
            'register' => [trans('webauthn::errors.cannot_register_new_key')],
        ]);
    }
}
