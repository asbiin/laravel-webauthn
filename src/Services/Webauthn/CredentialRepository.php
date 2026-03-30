<?php

namespace LaravelWebauthn\Services\Webauthn;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Support\Collection;
use LaravelWebauthn\Facades\Webauthn;
use Webauthn\CredentialRecord;
use Webauthn\PublicKeyCredentialDescriptor;

class CredentialRepository
{
    /**
     * List of CredentialRecord associated to the user.
     *
     * @return Collection<array-key,CredentialRecord>
     */
    protected function getAllRegisteredKeys(int|string $userId): Collection
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
    public function getRegisteredKeys(User $user): array
    {
        return $this->getAllRegisteredKeys($user->getAuthIdentifier())
            ->map
            ->getPublicKeyCredentialDescriptor()
            ->toArray();
    }
}
