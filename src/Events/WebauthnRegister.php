<?php

namespace LaravelWebauthn\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WebauthnRegister
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $webauthnKey  The new WebauthnKey.
     */
    public function __construct(
        public Model $webauthnKey
    ) {
    }
}
