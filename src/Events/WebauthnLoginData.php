<?php

namespace LaravelWebauthn\Events;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Queue\SerializesModels;
use Webauthn\PublicKeyCredentialRequestOptions;

class WebauthnLoginData
{
    use SerializesModels;

    /**
     * The authenticated user.
     *
     * @var \Illuminate\Contracts\Auth\Authenticatable
     */
    public $user;

    /**
     * The authentication data.
     *
     * @var PublicKeyCredentialRequestOptions
     */
    public $publicKey;

    /**
     * Create a new event instance.
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param PublicKeyCredentialRequestOptions  $publicKey
     */
    public function __construct(User $user, PublicKeyCredentialRequestOptions $publicKey)
    {
        $this->user = $user;
        $this->publicKey = $publicKey;
    }
}
