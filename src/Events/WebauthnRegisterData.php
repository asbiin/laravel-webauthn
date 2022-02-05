<?php

namespace LaravelWebauthn\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use Webauthn\PublicKeyCredentialCreationOptions;

class WebauthnRegisterData extends EventUser
{
    /**
     * The register data.
     *
     * @var PublicKeyCredentialCreationOptions
     */
    public $publicKey;

    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  PublicKeyCredentialCreationOptions  $publicKey
     */
    public function __construct(Authenticatable $user, PublicKeyCredentialCreationOptions $publicKey)
    {
        parent::__construct($user);
        $this->publicKey = $publicKey;
    }
}
