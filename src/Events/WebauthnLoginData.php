<?php

namespace LaravelWebauthn\Events;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Webauthn\PublicKeyCredentialRequestOptions;

/**
 * @psalm-suppress PossiblyUnusedProperty
 */
class WebauthnLoginData
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user  The authenticated user.
     * @param  PublicKeyCredentialRequestOptions  $publicKey  The authentication data.
     */
    public function __construct(
        public ?User $user,
        public PublicKeyCredentialRequestOptions $publicKey
    ) {}
}
