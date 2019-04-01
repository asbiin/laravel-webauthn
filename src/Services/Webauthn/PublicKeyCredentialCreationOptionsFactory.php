<?php

namespace LaravelWebauthn\Services\Webauthn;

use Illuminate\Support\Facades\Request;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\PublicKeyCredentialCreationOptions;
use Illuminate\Contracts\Auth\Authenticatable as User;

final class PublicKeyCredentialCreationOptionsFactory extends AbstractOptions
{
    /**
     * @param User $user
     * @param array[AuthenticationExtensionsClientInputs] $excludeCredentials
     */
    public function create(User $user, array $excludeCredentials = []): PublicKeyCredentialCreationOptions
    {
        $userEntity = new PublicKeyCredentialUserEntity(
            $user->email ?: '',
            $user->getAuthIdentifier(),
            $user->email ?: '',
            null
        );

        return new PublicKeyCredentialCreationOptions(
            $this->createRpEntity(),
            $userEntity,
            random_bytes($this->config->get('webauthn.challenge_length')),
            $this->createCredentialParameters(),
            $this->config->get('webauthn.timeout'),
            $excludeCredentials,
            $this->createAuthenticatorSelectionCriteria(),
            $this->config->get('webauthn.attestation_conveyance') ?: PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,
            $this->createExtensions()
        );
    }

    private function createAuthenticatorSelectionCriteria(): AuthenticatorSelectionCriteria
    {
        return new AuthenticatorSelectionCriteria(
            $this->config->get('webauthn.authenticator_selection_criteria.attachment_mode') ?: AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_NO_PREFERENCE,
            $this->config->get('webauthn.authenticator_selection_criteria.require_resident_key'),
            $this->config->get('webauthn.authenticator_selection_criteria.user_verification') ?: AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_PREFERRED
        );
    }

    private function createRpEntity(): PublicKeyCredentialRpEntity
    {
        return new PublicKeyCredentialRpEntity(
            $this->config->get('app.name'),
            Request::getHttpHost(),
            $this->config->get('webauthn.icon')
        );
    }

    /**
     * @return PublicKeyCredentialParameters[]
     */
    private function createCredentialParameters(): array
    {
        $callback = function ($algorithm) {
            return new PublicKeyCredentialParameters(
                PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
                $algorithm
            );
        };

        return array_map($callback, $this->config->get('public_key_credential_parameters') ?: [
            PublicKeyCredentialParameters::ALGORITHM_ES256,
            PublicKeyCredentialParameters::ALGORITHM_RS256,
        ]);
    }
}
