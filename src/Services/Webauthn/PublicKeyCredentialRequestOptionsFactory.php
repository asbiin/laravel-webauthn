<?php

namespace LaravelWebauthn\Services\Webauthn;

use Illuminate\Support\Facades\Request;
use Illuminate\Contracts\Config\Repository;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\AuthenticationExtensions\AuthenticationExtension;
use Webauthn\AuthenticationExtensions\AuthenticationExtensionsClientInputs;

final class PublicKeyCredentialRequestOptionsFactory extends AbstractOptions
{
    public function create(array $allowCredentials): PublicKeyCredentialRequestOptions
    {
        return new PublicKeyCredentialRequestOptions(
            random_bytes($this->config->get('webauthn.challenge_length')),
            $this->config->get('webauthn.timeout'),
            Request::getHttpHost(),
            $allowCredentials,
            $this->config->get('webauthn.authenticator_selection_criteria.user_verification') ?: AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_PREFERRED,
            $this->createExtensions()
        );
    }
}
