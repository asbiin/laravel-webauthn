<?php

namespace LaravelWebauthn\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * @psalm-suppress PossiblyUnusedProperty
 */
class WebauthnLogin
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  Authenticatable  $user  The authenticated user.
     * @param  bool  $eloquent  Login via eloquent webauthn provider.
     *
     * @psalm-mutation-free
     */
    public function __construct(
        public User $user,
        public bool $eloquent = false
    ) {}
}
