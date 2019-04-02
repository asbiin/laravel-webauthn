<?php

namespace LaravelWebauthn\Services\Webauthn;

use Illuminate\Support\Facades\Auth;
use Webauthn\AttestedCredentialData;
use LaravelWebauthn\Models\WebauthnKey;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\PublicKeyCredentialSourceRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CredentialRepository implements PublicKeyCredentialSourceRepository
{
    /**
     * Return a PublicKeyCredentialSource object.
     *
     * @param string $publicKeyCredentialId
     * @return null|PublicKeyCredentialSource
     */
    public function findOneByCredentialId(string $publicKeyCredentialId): ?PublicKeyCredentialSource
    {
        try {
            $webauthnKey = $this->model($publicKeyCredentialId);
            if ($webauthnKey) {
                return $webauthnKey->getPublicKeyCredentialSource();
            }
        } catch (ModelNotFoundException $e) {
            // No result
        }

        return null;
    }

    /**
     * Return a list of PublicKeyCredentialSource objects.
     *
     * @param PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity
     * @return PublicKeyCredentialSource[]
     */
    public function findAllForUserEntity(PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity): array
    {
        return WebauthnKey::where('user_id', $publicKeyCredentialUserEntity->getId())
            ->get()
            ->map(function ($webauthnKey) {
                return $webauthnKey->getPublicKeyCredentialSource();
            })
            ->toArray();
    }

    /**
     * Save a PublicKeyCredentialSource object.
     *
     * @param PublicKeyCredentialSource $publicKeyCredentialSource
     * @throws ModelNotFoundException
     */
    public function saveCredentialSource(PublicKeyCredentialSource $publicKeyCredentialSource): void
    {
        $webauthnKey = $this->model($publicKeyCredentialSource->getPublicKeyCredentialId());
        if ($webauthnKey) {
            $webauthnKey->setPublicKeyCredentialSource($publicKeyCredentialSource);
            $webauthnKey->save();
        }
    }

    /**
     * Get one WebauthnKey.
     *
     * @param string $credentialId
     * @return WebauthnKey|null
     * @throws ModelNotFoundException
     */
    private function model(string $credentialId)
    {
        $userId = Auth::id();
        if ($userId) {
            /** @var WebauthnKey */
            $webauthnKey = WebauthnKey::where([
                'user_id' => $userId,
                'credentialId' => base64_encode($credentialId),
            ])->firstOrFail();
            return $webauthnKey;
        }
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
