<?php

namespace LaravelWebauthn\Services;

use LaravelWebauthn\Models\WebauthnKey;
use Webauthn\PublicKeyCredentialSource;
use Illuminate\Contracts\Auth\Authenticatable as User;

abstract class WebauthnRepository
{
    /**
     * @param User $user
     * @param string $keyName
     * @param PublicKeyCredentialSource $publicKeyCredentialSource
     * @return WebauthnKey
     */
    public function create(User $user, string $keyName, PublicKeyCredentialSource $publicKeyCredentialSource)
    {
        $webauthnKey = WebauthnKey::make([
            'user_id' => $user->getAuthIdentifier(),
            'name' => $keyName,
        ]);
        $webauthnKey->publicKeyCredentialSource = $publicKeyCredentialSource;
        $webauthnKey->save();

        return $webauthnKey;
    }

    /**
     * Detect if user has a key.
     *
     * @param User $user
     * @return bool
     */
    public function hasKey(User $user): bool
    {
        return WebauthnKey::where('user_id', $user->getAuthIdentifier())->count() > 0;
    }
}
