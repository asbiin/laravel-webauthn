<?php

namespace LaravelWebauthn;

use Illuminate\Database\Eloquent\Relations\HasMany;
use LaravelWebauthn\Models\WebauthnKey;

trait WebauthnAuthenticatable
{
    /**
     * Get the webauthn keys associated to this user.
     *
     * @return HasMany
     */
    public function webauthnKeys()
    {
        return $this->hasMany(WebauthnKey::class);
    }
}
