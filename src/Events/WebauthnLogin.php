<?php

namespace LaravelWebauthn\Events;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WebauthnLogin
{
    use SerializesModels, Dispatchable;

    /**
     * The authenticated user.
     *
     * @var \Illuminate\Contracts\Auth\Authenticatable
     */
    public User $user;

    /**
     * Login via eloquent webauthn provider.
     *
     * @var bool
     */
    public bool $eloquent;

    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  bool  $eloquent
     */
    public function __construct(User $user, bool $eloquent = false)
    {
        $this->user = $user;
        $this->eloquent = $eloquent;
    }
}
