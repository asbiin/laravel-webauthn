<?php

namespace LaravelWebauthn\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use LaravelWebauthn\Facades\Webauthn;
use Webauthn\PublicKeyCredentialRequestOptions;

class PrepareAssertionData
{
    /**
     * Get data to authenticate a user.
     *
     * @param  Authenticatable  $user
     * @return PublicKeyCredentialRequestOptions
     */
    public function __invoke(Authenticatable $user): PublicKeyCredentialRequestOptions
    {
        return Webauthn::prepareAssertion($user);
    }
}
