<?php

namespace LaravelWebauthn\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use LaravelWebauthn\Facades\Webauthn;
use LaravelWebauthn\Models\WebauthnKey;

class UpdateKey
{
    /**
     * Update a key.
     *
     * @param  Authenticatable  $user
     * @param  int  $webauthnKeyId
     * @return WebauthnKey
     */
    public function __invoke(Authenticatable $user, int $webauthnKeyId, string $keyName): WebauthnKey
    {
        $webauthnKey = (Webauthn::model())::where('user_id', $user->getAuthIdentifier())
            ->findOrFail($webauthnKeyId);

        $webauthnKey->name = $keyName;
        $webauthnKey->save();

        return $webauthnKey;
    }
}
