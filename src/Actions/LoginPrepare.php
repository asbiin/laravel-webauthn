<?php

namespace LaravelWebauthn\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use LaravelWebauthn\Events\WebauthnLoginData;
use LaravelWebauthn\Services\Webauthn\PublicKeyCredentialRequestOptionsFactory;
use Webauthn\PublicKeyCredentialRequestOptions;

class LoginPrepare
{
    /**
     * Get data to authenticate a user.
     *
     * @param  Authenticatable  $user
     * @return PublicKeyCredentialRequestOptions
     */
    public function __invoke(Authenticatable $user): PublicKeyCredentialRequestOptions
    {
        $publicKey = app(PublicKeyCredentialRequestOptionsFactory::class)($user);

        WebauthnLoginData::dispatch($user, $publicKey);

        return $publicKey;
    }
}
