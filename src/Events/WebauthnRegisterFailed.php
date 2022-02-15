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
     * The authenticated user.
     *
     * @var \Illuminate\Contracts\Auth\Authenticatable
     */
    public User $user;

    /**
     * Exception throwned.
     *
     * @var ?Exception
     */
    public ?Exception $exception;

    /**
     * Create a new event instance.
     *
     * @param  Authenticatable  $user
     * @param  Exception|null  $exception
     */
    public function __construct(User $user, ?Exception $exception = null)
    {
        $this->user = $user;
        $this->exception = $exception;
    }
}
