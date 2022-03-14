<?php

namespace LaravelWebauthn\Events;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Webauthn\PublicKeyCredentialCreationOptions;

class WebauthnRegisterData
{
    use SerializesModels, Dispatchable;

    /**
     * The authenticated user.
     *
     * @var \Illuminate\Contracts\Auth\Authenticatable
     */
    public User $user;

    /**
     * The register data.
     *
     * @var PublicKeyCredentialCreationOptions
     */
    public PublicKeyCredentialCreationOptions $publicKey;

    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  PublicKeyCredentialCreationOptions  $publicKey
     */
    public function __construct(User $user, PublicKeyCredentialCreationOptions $publicKey)
    {
        $this->user = $user;
        $this->publicKey = $publicKey;
    }
}
