<?php

namespace LaravelWebauthn;

use Illuminate\Database\Eloquent\Relations\HasMany;
use LaravelWebauthn\Models\WebauthnKey;

/**
 * Trait to add Webauthn authenticatable to a user model.
 *
 * @phpstan-ignore trait.unused
 */
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
