<?php

namespace LaravelWebauthn\Events;

use Exception;
use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * @psalm-suppress PossiblyUnusedProperty
 */
class WebauthnRegisterFailed
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user  The authenticated user.
     * @param  Exception|null  $exception  Exception throwned.
     *
     * @psalm-suppress PossiblyUnusedProperty
     */
    public function __construct(
        public User $user,
        public ?Exception $exception = null
    ) {}
}
