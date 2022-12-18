<?php

namespace LaravelWebauthn\Contracts;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;

interface CredentialRepository
{
    /**
     * Return a PublicKeyCredentialSource object.
     *
     * @param  string  $publicKeyCredentialId
     * @return null|PublicKeyCredentialSource
     */
    public function findOneByCredentialId(string $publicKeyCredentialId): ?PublicKeyCredentialSource;

    /**
     * Return a list of PublicKeyCredentialSource objects.
     *
     * @param  PublicKeyCredentialUserEntity  $publicKeyCredentialUserEntity
     * @return PublicKeyCredentialSource[]
     */
    public function findAllForUserEntity(PublicKeyCredentialUserEntity $publicKeyCredentialUserEntity): array;

    /**
     * Save a PublicKeyCredentialSource object.
     *
     * @param  PublicKeyCredentialSource  $publicKeyCredentialSource
     *
     * @throws ModelNotFoundException
     */
    public function saveCredentialSource(PublicKeyCredentialSource $publicKeyCredentialSource): void;

    /**
     * List of registered PublicKeyCredentialDescriptor associated to the user.
     *
     * @param  User  $user
     * @return PublicKeyCredentialDescriptor[]
     */
    public function getRegisteredKeys(User $user);
}
