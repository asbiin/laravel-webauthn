<?php

namespace LaravelWebauthn\Events;

use Illuminate\Queue\SerializesModels;
use Webauthn\PublicKeyCredentialRequestOptions;
use Illuminate\Contracts\Auth\Authenticatable as User;

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
