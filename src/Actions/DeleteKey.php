<?php

namespace LaravelWebauthn\Actions;

use Illuminate\Contracts\Auth\Authenticatable as User;
use LaravelWebauthn\Facades\Webauthn;

class DeleteKey
{
    /**
     * Delete a key.
     */
    public function __invoke(User $user, int $webauthnKeyId): void
    {
        (Webauthn::model())::where('user_id', $user->getAuthIdentifier())
            ->findOrFail($webauthnKeyId)
            ->delete();

        if (! Webauthn::hasKey($user)) {
            // Remove session value when last key is deleted
            Webauthn::logout();
        }
    }
}
