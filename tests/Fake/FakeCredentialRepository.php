<?php

namespace LaravelWebauthn\Tests\Fake;

use Webauthn\AttestedCredentialData;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\PublicKeyCredentialSourceRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class FakeCredentialRepository implements PublicKeyCredentialSourceRepository
{
    private $publicKeyCredentialSources = array();

    public function findOneByCredentialId(string $publicKeyCredentialId): ?PublicKeyCredentialSource
    {
        if (array_has($this->publicKeyCredentialSources, $publicKeyCredentialId)) {
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
