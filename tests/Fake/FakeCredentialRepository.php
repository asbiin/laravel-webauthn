<?php

namespace LaravelWebauthn\Tests\Fake;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Support\Arr;
use LaravelWebauthn\Models\WebauthnKey;
use LaravelWebauthn\Services\Webauthn\CredentialRepository;
use Webauthn\AttestedCredentialData;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;

class FakeCredentialRepository extends CredentialRepository
{
    public function create(User $user, string $keyName, PublicKeyCredentialSource $publicKeyCredentialSource)
    {
        $webauthnKey = factory(WebauthnKey::class)->create([
            'user_id' => $user->getAuthIdentifier(),
            'name' => $keyName,
        ]);
        $webauthnKey->publicKeyCredentialSource = $publicKeyCredentialSource;
        $webauthnKey->save();

        $this->saveCredentialSource($publicKeyCredentialSource);

        return $webauthnKey;
    }

    /**
     * List of registered PublicKeyCredentialDescriptor classes associated to the user.
     * @param User $user
     * @return PublicKeyCredentialDescriptor[]
     */
    public function getRegisteredKeys(User $user): array
    {
        return collect($this->publicKeyCredentialSources)
            ->map(function ($publicKey) {
                return $publicKey->getPublicKeyCredentialDescriptor();
            })
            ->toArray();
    }

    /**
     * Detect if user has a key.
     *
     * @param User $user
     * @return bool
     */
    public function hasKey(User $user): bool
    {
        return $this->has($user->getAuthIdentifier());
    }

    private $publicKeyCredentialSources = [];

    public function findOneByCredentialId(string $publicKeyCredentialId): ?PublicKeyCredentialSource
    {
        if (Arr::has($this->publicKeyCredentialSources, $publicKeyCredentialId)) {
            return $this->publicKeyCredentialSources[$publicKeyCredentialId];
        }

        return null;
    }

    public function findAllForUserEntity(PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity): array
    {
        return $this->publicKeyCredentialSources;
    }

    public function saveCredentialSource(PublicKeyCredentialSource $publicKeyCredentialSource): void
    {
        $this->publicKeyCredentialSources[$publicKeyCredentialSource->getPublicKeyCredentialId()] = $publicKeyCredentialSource;
    }

    public function has(string $credentialId): bool
    {
        return $this->findOneByCredentialId($credentialId) !== null;
    }

    public function get(string $credentialId): AttestedCredentialData
    {
        return $this->findOneByCredentialId($credentialId)->getAttestedCredentialData();
    }

    public function getUserHandleFor(string $credentialId): string
    {
        return $this->findOneByCredentialId($credentialId)->getUserHandle();
    }

    public function getCounterFor(string $credentialId): int
    {
        return $this->findOneByCredentialId($credentialId)->getCounter();
    }

    public function updateCounterFor(string $credentialId, int $newCounter): void
    {
        $publicKeyCredentialSource = $this->findOneByCredentialId($credentialId);
        $publicKeyCredentialSource->setCounter($newCounter);
    }
}
