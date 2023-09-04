<?php

namespace LaravelWebauthn\Actions;

use Illuminate\Contracts\Auth\Authenticatable as User;
use LaravelWebauthn\Facades\Webauthn;
use Webauthn\PublicKeyCredentialRequestOptions;

class PrepareAssertionData
{
    /**
     * Get data to authenticate a user.
     */
    public function __invoke(User $user): PublicKeyCredentialRequestOptions
    {
        return Webauthn::prepareAssertion($user);
    }
}
