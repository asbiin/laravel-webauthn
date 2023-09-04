<?php

namespace LaravelWebauthn\Events;

use Exception;
use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WebauthnRegisterFailed
{
    use SerializesModels, Dispatchable;

    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user  The authenticated user.
     * @param  Exception|null  $exception  Exception throwned.
     */
    public function __construct(
        public User $user,
        public ?Exception $exception = null
    ) {
    }
}
