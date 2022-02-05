<?php

namespace LaravelWebauthn\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Foundation\Application;
use LaravelWebauthn\Events\WebauthnLoginData;
use LaravelWebauthn\Services\Webauthn\RequestOptionsFactory;
use Webauthn\PublicKeyCredentialRequestOptions;

class LoginPrepare
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
     * Get data to authenticate a user.
     *
     * @param  Authenticatable  $user
     * @return PublicKeyCredentialRequestOptions
     */
    public function __invoke(Authenticatable $user): PublicKeyCredentialRequestOptions
    {
        $publicKey = $this->app[RequestOptionsFactory::class]($user);

        WebauthnLoginData::dispatch($user, $publicKey);

        return $publicKey;
    }
}
