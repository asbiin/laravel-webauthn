<?php

namespace LaravelWebauthn\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

abstract class EventUser
{
    use SerializesModels, Dispatchable;

    /**
     * The authenticated user.
     *
     * @var \Illuminate\Contracts\Auth\Authenticatable
     */
    public $user;

    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     */
    public function __construct(Authenticatable $user)
    {
        $this->user = $user;
    }
}
