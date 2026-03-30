<?php

namespace LaravelWebauthn\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * @psalm-suppress PossiblyUnusedProperty
 */
class WebauthnAuthenticate
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  Model  $webauthnKey  The WebauthnKey used to authenticate.
     *
     * @psalm-mutation-free
     */
    public function __construct(
        public Model $webauthnKey,
    ) {}
}
