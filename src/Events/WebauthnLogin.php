<?php

namespace LaravelWebauthn\Events;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WebauthnLogin
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user  The authenticated user.
     * @param  bool  $eloquent  Login via eloquent webauthn provider.
     */
    public function __construct(
        public User $user,
        public bool $eloquent = false
    ) {
    }
}
