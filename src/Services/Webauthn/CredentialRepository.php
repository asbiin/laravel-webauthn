<?php

namespace LaravelWebauthn\Services\Webauthn;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Support\Collection;
use LaravelWebauthn\Facades\Webauthn;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialSource;

class CredentialRepository
{
    /**
     * List of PublicKeyCredentialSource associated to the user.
     *
     * @return Collection<array-key,PublicKeyCredentialSource>
     */
    protected static function getAllRegisteredKeys(int|string $userId): Collection
    {
        return (Webauthn::model())::where('user_id', $userId)
            ->get()
            ->map
            ->publicKeyCredentialSource;
    }

    /**
     * List of registered PublicKeyCredentialDescriptor associated to the user.
     *
     * @return array<array-key,PublicKeyCredentialDescriptor>
     */
    public static function getRegisteredKeys(User $user): array
    {
        return static::getAllRegisteredKeys($user->getAuthIdentifier())
            ->map
            ->getPublicKeyCredentialDescriptor()
            ->toArray();
    }
}
