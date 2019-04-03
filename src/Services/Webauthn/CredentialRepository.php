<?php

namespace LaravelWebauthn\Services\Webauthn;

use Illuminate\Support\Facades\Auth;
use Webauthn\AttestedCredentialData;
use LaravelWebauthn\Models\WebauthnKey;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\PublicKeyCredentialSourceRepository;
use Illuminate\Contracts\Auth\Authenticatable as User;
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
                return $webauthnKey->publicKeyCredentialSource;
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
        return $this->getAllRegisteredKeys($publicKeyCredentialUserEntity->getId())
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
            $webauthnKey->publicKeyCredentialSource = $publicKeyCredentialSource;
            $webauthnKey->save();
        }
    }

    /**
     * List of PublicKeyCredentialSource associated to the user.
     *
     * @param int|string $userId
     * @return \Illuminate\Support\Collection collection of PublicKeyCredentialSource
     */
    protected function getAllRegisteredKeys($userId)
    {
        return WebauthnKey::where('user_id', $userId)
            ->get()
            ->map(function ($webauthnKey) {
                return $webauthnKey->publicKeyCredentialSource;
            });
    }

    /**
     * List of registered PublicKeyCredentialDescriptor associated to the user.
     *
     * @param User $user
     * @return PublicKeyCredentialDescriptor[]
     */
    public function getRegisteredKeys(User $user): array
    {
        return $this->getAllRegisteredKeys($user->getAuthIdentifier())
            ->map(function ($publicKey) {
                return $publicKey->getPublicKeyCredentialDescriptor();
            })
            ->toArray();
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

    // deprecated CredentialRepository interface :

    /**
     * @codeCoverageIgnore
     */
    public function has(string $credentialId): bool
    {
        return $this->findOneByCredentialId($credentialId) !== null;
    }

    /**
     * @codeCoverageIgnore
     */
    public function get(string $credentialId): AttestedCredentialData
    {
        $publicKeyCredentialSource = $this->findOneByCredentialId($credentialId);
        if (! $publicKeyCredentialSource) {
            throw new ModelNotFoundException('Wrong credentialId');
        }

        return $publicKeyCredentialSource->getAttestedCredentialData();
    }

    /**
     * @codeCoverageIgnore
     */
    public function getUserHandleFor(string $credentialId): string
    {
        $publicKeyCredentialSource = $this->findOneByCredentialId($credentialId);
        if (! $publicKeyCredentialSource) {
            throw new ModelNotFoundException('Wrong credentialId');
        }

        return $publicKeyCredentialSource->getUserHandle();
    }

    /**
     * @codeCoverageIgnore
     */
    public function getCounterFor(string $credentialId): int
    {
        $publicKeyCredentialSource = $this->findOneByCredentialId($credentialId);
        if (! $publicKeyCredentialSource) {
            throw new ModelNotFoundException('Wrong credentialId');
        }

        return $publicKeyCredentialSource->getCounter();
    }

    /**
     * @codeCoverageIgnore
     */
    public function updateCounterFor(string $credentialId, int $newCounter): void
    {
        $publicKeyCredentialSource = $this->findOneByCredentialId($credentialId);
        if (! $publicKeyCredentialSource) {
            throw new ModelNotFoundException('Wrong credentialId');
        }
        $publicKeyCredentialSource->setCounter($newCounter);
    }
}
