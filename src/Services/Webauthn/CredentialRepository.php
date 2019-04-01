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
    public function has(string $credentialId): bool
    {
        $userId = Auth::id();
        if ($userId) {
            return WebauthnKey::where([
                'user_id' => $userId,
                'credentialId' => base64_encode($credentialId),
            ])->count() !== 0;
        }

        return false;
    }

    public function get(string $credentialId): AttestedCredentialData
    {
        $webAuthn = $this->model($credentialId);

        return $webAuthn->attestedCredentialData;
    }

    public function getUserHandleFor(string $credentialId): string
    {
        $webAuthn = $this->model($credentialId);

        return $webAuthn->user_id;
    }

    public function getCounterFor(string $credentialId): int
    {
        $webAuthn = $this->model($credentialId);

        return $webAuthn->counter;
    }

    public function updateCounterFor(string $credentialId, int $newCounter): void
    {
        $webAuthn = $this->model($credentialId);
        $webAuthn->counter = $newCounter;
        $webAuthn->save();
    }

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
     * @throws ModelNotFoundException
     */
    public function saveCredentialSource(PublicKeyCredentialSource $publicKeyCredentialSource): void
    {
        $webauthnKey = $this->model($publicKeyCredentialSource->getPublicKeyCredentialId());
        if (! $webauthnKey) {
            $webauthnKey = new WebauthnKey();
        }
        $webauthnKey->setPublicKeyCredentialSource($publicKeyCredentialSource);
        $webauthnKey->save();
    }

    private function model(string $credentialId)
    {
        $userId = Auth::id();
        if ($userId) {
            return WebauthnKey::where([
                'user_id' => $userId,
                'credentialId' => base64_encode($credentialId),
            ])->firstOrFail();
        }
    }
}
