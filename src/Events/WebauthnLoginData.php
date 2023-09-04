<?php

namespace LaravelWebauthn\Events;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Webauthn\PublicKeyCredentialRequestOptions;

class WebauthnLoginData
{
    use SerializesModels, Dispatchable;

    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user  The authenticated user.
     * @param  PublicKeyCredentialRequestOptions  $publicKey  The authentication data.
     */
    public function __construct(
        public User $user,
        public PublicKeyCredentialRequestOptions $publicKey
    ) {
    }
}
