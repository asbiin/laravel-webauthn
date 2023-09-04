<?php

namespace LaravelWebauthn;

use Illuminate\Database\Eloquent\Relations\HasMany;
use LaravelWebauthn\Models\WebauthnKey;

trait WebauthnAuthenticatable
{
    /**
     * Get the webauthn keys associated to this user.
     */
    public function webauthnKeys(): HasMany
    {
        return $this->hasMany(WebauthnKey::class);
    }
}
