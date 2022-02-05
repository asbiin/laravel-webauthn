<?php

namespace LaravelWebauthn\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use Webauthn\PublicKeyCredentialRequestOptions;

class WebauthnLoginData extends EventUser
{
    /**
     * The authentication data.
     *
     * @var PublicKeyCredentialRequestOptions
     */
    public $publicKey;

    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  PublicKeyCredentialRequestOptions  $publicKey
     */
    public function __construct(Authenticatable $user, PublicKeyCredentialRequestOptions $publicKey)
    {
        parent::__construct($user);
        $this->publicKey = $publicKey;
    }
}
