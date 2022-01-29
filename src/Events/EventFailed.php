<?php

namespace LaravelWebauthn\Events;

use Exception;
use Illuminate\Contracts\Auth\Authenticatable;

abstract class EventFailed extends EventUser
{
    /**
     * Exception throwned.
     *
     * @var ?Exception
     */
    public $exception;

    /**
     * Create a new event instance.
     *
     * @param  Authenticatable  $user
     * @param  Exception|null  $exception
     */
    public function __construct(Authenticatable $user, ?Exception $exception = null)
    {
        parent::__construct($user);
        $this->exception = $exception;
    }
}
