<?php

namespace LaravelWebauthn\Services;

use Illuminate\Contracts\Auth\Authenticatable as User;
use LaravelWebauthn\Models\WebauthnKey;
use Webauthn\PublicKeyCredentialSource;

abstract class WebauthnRepository
{
    /**
     * Create a new key.
     *
     * @param  User  $user
     * @param  string  $keyName
     * @param  PublicKeyCredentialSource  $publicKeyCredentialSource
     * @return WebauthnKey
     */
    public static function create(User $user, string $keyName, PublicKeyCredentialSource $publicKeyCredentialSource)
    {
        $webauthnKey = new WebauthnKey();
        $webauthnKey->user_id = $user->getAuthIdentifier();
        $webauthnKey->name = $keyName;
        $webauthnKey->publicKeyCredentialSource = $publicKeyCredentialSource;
        $webauthnKey->save();

        return $webauthnKey;
    }

    /**
     * Detect if user has a key.
     *
     * @param  User  $user
     * @return bool
     */
    public static function hasKey(User $user): bool
    {
        return WebauthnKey::where('user_id', $user->getAuthIdentifier())->count() > 0;
    }
}
