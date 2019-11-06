<?php

namespace LaravelWebauthn\Events;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Queue\SerializesModels;
use Webauthn\PublicKeyCredentialCreationOptions;

class WebauthnRegisterData
{
    use SerializesModels;

    /**
     * The authenticated user.
     *
     * @var \Illuminate\Contracts\Auth\Authenticatable
     */
    public $user;

    /**
     * The register data.
     *
     * @var PublicKeyCredentialCreationOptions
     */
    public $publicKey;

    /**
     * Create a new event instance.
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param PublicKeyCredentialCreationOptions  $publicKey
     */
    public function __construct(User $user, PublicKeyCredentialCreationOptions $publicKey)
    {
        $this->user = $user;
        $this->publicKey = $publicKey;
    }
}
