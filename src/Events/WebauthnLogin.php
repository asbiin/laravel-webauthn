<?php

namespace LaravelWebauthn\Events;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Queue\SerializesModels;

class WebauthnLogin
{
    use SerializesModels;

    /**
     * The authenticated user.
     *
     * @var \Illuminate\Contracts\Auth\Authenticatable
     */
    public $user;

    /**
     * Create a new event instance.
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable  $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }
}
