<?php

namespace LaravelWebauthn\Services\Webauthn;

use Base64Url\Base64Url;
use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use LaravelWebauthn\Models\WebauthnKey;
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
     * @param  \Illuminate\Contracts\Auth\Guard  $guard
     */
    public function __construct(Guard $guard)
    {
        $this->guard = $guard;
    }

    /**
     * Return a PublicKeyCredentialSource object.
     *
     * @param  string  $publicKeyCredentialId
     * @return null|PublicKeyCredentialSource
     */
    public function findOneByCredentialId(string $publicKeyCredentialId): ?PublicKeyCredentialSource
    {
        try {
            $webauthnKey = $this->model($publicKeyCredentialId);
            if ($webauthnKey !== null) {
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
     * @param  PublicKeyCredentialUserEntity  $publicKeyCredentialUserEntity
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
     * @param  PublicKeyCredentialSource  $publicKeyCredentialSource
     *
     * @throws ModelNotFoundException
     */
    public function saveCredentialSource(PublicKeyCredentialSource $publicKeyCredentialSource): void
    {
        $webauthnKey = $this->model($publicKeyCredentialSource->getPublicKeyCredentialId());
        if ($webauthnKey !== null) {
            $webauthnKey->publicKeyCredentialSource = $publicKeyCredentialSource;
            $webauthnKey->save();
        }
    }

    /**
     * List of PublicKeyCredentialSource associated to the user.
     *
     * @param  int|string  $userId
     * @return \Illuminate\Support\Collection collection of PublicKeyCredentialSource
     */
    protected function getAllRegisteredKeys($userId): \Illuminate\Support\Collection
    {
        return WebauthnKey::where('user_id', $userId)
            ->get()
            ->map
            ->publicKeyCredentialSource;
    }

    /**
     * List of registered PublicKeyCredentialDescriptor associated to the user.
     *
     * @param  User  $user
     * @return PublicKeyCredentialDescriptor[]
     */
    public function getRegisteredKeys(User $user): array
    {
        return $this->getAllRegisteredKeys($user->getAuthIdentifier())
            ->map
            ->getPublicKeyCredentialDescriptor()
            ->toArray();
    }

    /**
     * Get one WebauthnKey.
     *
     * @param  string  $credentialId
     * @return WebauthnKey|null
     *
     * @throws ModelNotFoundException
     */
    private function model(string $credentialId): ?WebauthnKey
    {
        if (! $this->guard->guest()) {
            /** @var WebauthnKey */
            $webauthnKey = WebauthnKey::where([
                'user_id' => $this->guard->id(),
                'credentialId' => Base64Url::encode($credentialId),
            ])->firstOrFail();

            return $webauthnKey;
        }

        return null;
    }
}
