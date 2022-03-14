<?php

namespace LaravelWebauthn\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WebauthnRegister
{
    use SerializesModels, Dispatchable;

    /**
     * The new WebauthnKey.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    public Model $webauthnKey;

    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $webauthnKey
     */
    public function __construct(Model $webauthnKey)
    {
        $this->webauthnKey = $webauthnKey;
    }
}
