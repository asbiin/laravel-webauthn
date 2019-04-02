<?php

namespace LaravelWebauthn\Events;

use Illuminate\Queue\SerializesModels;
use LaravelWebauthn\Models\WebauthnKey;

class WebauthnRegister
{
    use SerializesModels;

    /**
     * The new WebauthnKey.
     *
     * @var WebauthnKey
     */
    public $webauthnKey;

    /**
     * Create a new event instance.
     *
     * @param WebauthnKey  $webauthnKey
     */
    public function __construct(WebauthnKey $webauthnKey)
    {
        $this->webauthnKey = $webauthnKey;
    }
}
