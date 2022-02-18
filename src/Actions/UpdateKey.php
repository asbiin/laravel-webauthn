<?php

namespace LaravelWebauthn\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use LaravelWebauthn\Facades\Webauthn;

class UpdateKey
{
    /**
     * Update a key.
     *
     * @param  Authenticatable  $user
     * @param  int  $webauthnKeyId
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function __invoke(Authenticatable $user, int $webauthnKeyId, string $keyName): Model
    {
        $webauthnKey = (Webauthn::model())::where('user_id', $user->getAuthIdentifier())
            ->findOrFail($webauthnKeyId);

        $webauthnKey->name = $keyName;
        $webauthnKey->save();

        return $webauthnKey;
    }
}
