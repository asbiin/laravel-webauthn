<?php

namespace LaravelWebauthn\Services\Webauthn;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Support\Facades\Request;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\PublicKeyCredentialRequestOptions;

final class PublicKeyCredentialRequestOptionsFactory extends AbstractOptionsFactory
{
    /**
     * Create a new PublicKeyCredentialCreationOptions object.
     *
     * @param User $user
     * @return PublicKeyCredentialRequestOptions
     */
    public function create(User $user): PublicKeyCredentialRequestOptions
    {
        return new PublicKeyCredentialRequestOptions(
            random_bytes($this->config->get('webauthn.challenge_length', 32)),
            $this->config->get('webauthn.timeout', 60000),
            Request::getHttpHost(),
            $this->repository->getRegisteredKeys($user),
            $this->config->get('webauthn.authenticator_selection_criteria.user_verification') ?? AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_PREFERRED,
            $this->createExtensions()
        );
    }
}
