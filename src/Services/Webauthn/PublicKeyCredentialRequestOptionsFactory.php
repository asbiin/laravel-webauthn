<?php

namespace LaravelWebauthn\Services\Webauthn;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Support\Facades\Request;
use Webauthn\PublicKeyCredentialRequestOptions;

final class PublicKeyCredentialRequestOptionsFactory extends AbstractOptionsFactory
{
    /**
     * Create a new PublicKeyCredentialCreationOptions object.
     *
     * @param  User  $user
     * @return PublicKeyCredentialRequestOptions
     */
    public function __invoke(User $user): PublicKeyCredentialRequestOptions
    {
        return (new PublicKeyCredentialRequestOptions(
            random_bytes($this->config->get('webauthn.challenge_length', 32)),
            $this->config->get('webauthn.timeout', 60000),
            null,
            $this->repository->getRegisteredKeys($user),
            null,
            $this->createExtensions()
        ))
            ->setRpId(Request::getHttpHost())
            ->setUserVerification($this->config->get('webauthn.user_verification', 'preferred'));
    }
}
