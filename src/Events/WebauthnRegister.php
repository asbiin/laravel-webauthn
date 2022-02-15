<?php

namespace LaravelWebauthn\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use LaravelWebauthn\Models\WebauthnKey;

class WebauthnRegister
{
    use SerializesModels, Dispatchable;

    /**
     * The new WebauthnKey.
     *
     * @var WebauthnKey
     */
    public WebauthnKey $webauthnKey;

    /**
     * Create a new event instance.
     *
     * @param  WebauthnKey  $webauthnKey
     */
    public function __construct(WebauthnKey $webauthnKey)
    {
        $this->webauthnKey = $webauthnKey;
    }
}
