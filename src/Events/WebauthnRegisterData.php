<?php

namespace LaravelWebauthn\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Webauthn\PublicKeyCredentialCreationOptions;

/**
 * @psalm-suppress PossiblyUnusedProperty
 */
class WebauthnRegisterData
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  Authenticatable  $user  The authenticated user.
     * @param  PublicKeyCredentialCreationOptions  $publicKey  The register data.
     *
     * @psalm-suppress PossiblyUnusedProperty
     *
     * @psalm-mutation-free
     */
    public function __construct(
        public User $user,
        public PublicKeyCredentialCreationOptions $publicKey
    ) {}
}
