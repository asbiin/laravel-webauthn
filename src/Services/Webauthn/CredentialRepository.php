<?php

namespace LaravelWebauthn\Services\Webauthn;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use LaravelWebauthn\Models\WebauthnKey;
use Webauthn\AttestedCredentialData;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialSourceRepository;
use Webauthn\PublicKeyCredentialUserEntity;

class CredentialRepository implements PublicKeyCredentialSourceRepository
{
    /**
     * Guard instance;.
     *
     * @var \Illuminate\Contracts\Auth\Guard
     */
    protected $guard;

    /**
     * Create a new instance of Webauthn.
     *
     * @param \Illuminate\Contracts\Auth\Guard $guard
     */
    public function __construct(Guard $guard)
    {
        $this->guard = $guard;
    }

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
            if (! is_null($webauthnKey)) {
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
        if (! is_null($webauthnKey)) {
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
    private function model(string $credentialId): ?WebauthnKey
    {
        if (! $this->guard->guest()) {
            /** @var WebauthnKey */
            $webauthnKey = WebauthnKey::where([
                'user_id' => $this->guard->id(),
                'credentialId' => base64_encode($credentialId),
            ])->firstOrFail();

            return $webauthnKey;
        }

        return null;
    }

    // deprecated CredentialRepository interface :

    /**
     * @deprecated
     * @codeCoverageIgnore
     */
    public function has(string $credentialId): bool
    {
        return $this->findOneByCredentialId($credentialId) !== null;
    }

    /**
     * @deprecated
     * @codeCoverageIgnore
     */
    public function get(string $credentialId): AttestedCredentialData
    {
        $publicKeyCredentialSource = $this->findOneByCredentialId($credentialId);
        if (is_null($publicKeyCredentialSource)) {
            throw new ModelNotFoundException('Wrong credentialId');
        }

        return $publicKeyCredentialSource->getAttestedCredentialData();
    }

    /**
     * @deprecated
     * @codeCoverageIgnore
     */
    public function getUserHandleFor(string $credentialId): string
    {
        $publicKeyCredentialSource = $this->findOneByCredentialId($credentialId);
        if (is_null($publicKeyCredentialSource)) {
            throw new ModelNotFoundException('Wrong credentialId');
        }

        return $publicKeyCredentialSource->getUserHandle();
    }

    /**
     * @deprecated
     * @codeCoverageIgnore
     */
    public function getCounterFor(string $credentialId): int
    {
        $publicKeyCredentialSource = $this->findOneByCredentialId($credentialId);
        if (is_null($publicKeyCredentialSource)) {
            throw new ModelNotFoundException('Wrong credentialId');
        }

        return $publicKeyCredentialSource->getCounter();
    }

    /**
     * @deprecated
     * @codeCoverageIgnore
     */
    public function updateCounterFor(string $credentialId, int $newCounter): void
    {
        $publicKeyCredentialSource = $this->findOneByCredentialId($credentialId);
        if (is_null($publicKeyCredentialSource)) {
            throw new ModelNotFoundException('Wrong credentialId');
        }
        $publicKeyCredentialSource->setCounter($newCounter);
    }
}
