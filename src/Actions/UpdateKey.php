<?php

namespace LaravelWebauthn\Actions;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Database\Eloquent\Model;
use LaravelWebauthn\Facades\Webauthn;

class UpdateKey
{
    /**
     * Update a key.
     */
    public function __invoke(User $user, int $webauthnKeyId, string $keyName): Model
    {
        $webauthnKey = (Webauthn::model())::where('user_id', $user->getAuthIdentifier())
            ->findOrFail($webauthnKeyId);

        // prevent timestamp update
        $timestamps = $webauthnKey->timestamps;
        $webauthnKey->timestamps = false;

        $webauthnKey->name = $keyName;
        $webauthnKey->save();

        $webauthnKey->timestamps = $timestamps;

        return $webauthnKey;
    }
}
