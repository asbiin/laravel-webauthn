<?php

namespace LaravelWebauthn\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use LaravelWebauthn\Models\WebauthnKey;

class UpdateKey
{
    /**
     * Update a key.
     *
     * @param  Authenticatable  $user
     * @param  int  $webauthnKeyId
     * @return void
     */
    public function __invoke(Authenticatable $user, int $webauthnKeyId, string $keyName): void
    {
        $webauthnKey = WebauthnKey::where('user_id', $user->getAuthIdentifier())
            ->findOrFail($webauthnKeyId);

        $webauthnKey->name = $keyName;
        $webauthnKey->save();
    }
}
