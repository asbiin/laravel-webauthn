<?php

namespace LaravelWebauthn\Services\Webauthn;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Support\Facades\Request;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;

final class PublicKeyCredentialCreationOptionsFactory extends AbstractOptionsFactory
{
    /**
     * Create a new PublicKeyCredentialCreationOptions object.
     *
     * @param  User  $user
     * @return PublicKeyCredentialCreationOptions
     */
    public function __invoke(User $user): PublicKeyCredentialCreationOptions
    {
        $userEntity = new PublicKeyCredentialUserEntity(
            $user->email ?? '',
            $user->getAuthIdentifier(),
            $user->email ?? '',
            null
        );

        return (new PublicKeyCredentialCreationOptions(
            $this->createRpEntity(),
            $userEntity,
            random_bytes($this->config->get('webauthn.challenge_length', 32)),
            $this->createCredentialParameters(),
            $this->config->get('webauthn.timeout', 60000),
            [],
            null,
            null,
            $this->createExtensions()
        ))
            ->excludeCredentials($this->repository->getRegisteredKeys($user))
            ->setAuthenticatorSelection($this->createAuthenticatorSelectionCriteria())
            ->setAttestation($this->config->get('webauthn.attestation_conveyance', 'none'));
    }

    private function createAuthenticatorSelectionCriteria(): AuthenticatorSelectionCriteria
    {
        return (new AuthenticatorSelectionCriteria())
            ->setAuthenticatorAttachment($this->config->get('webauthn.attachment_mode', 'null'))
            ->setUserVerification($this->config->get('webauthn.user_verification', 'preferred'));
    }

    private function createRpEntity(): PublicKeyCredentialRpEntity
    {
        return new PublicKeyCredentialRpEntity(
            $this->config->get('app.name', 'Laravel'),
            Request::getHost(),
            $this->config->get('webauthn.icon')
        );
    }

    /**
     * @return PublicKeyCredentialParameters[]
     */
    private function createCredentialParameters(): array
    {
        $callback = function ($algorithm): PublicKeyCredentialParameters {
            return new PublicKeyCredentialParameters(
                PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
                $algorithm
            );
        };

        return array_map($callback, $this->config->get('public_key_credential_parameters') ?? [
            \Cose\Algorithms::COSE_ALGORITHM_ES256,
            \Cose\Algorithms::COSE_ALGORITHM_ES512,
            \Cose\Algorithms::COSE_ALGORITHM_RS256,
            \Cose\Algorithms::COSE_ALGORITHM_EdDSA,
            \Cose\Algorithms::COSE_ALGORITHM_ES384,
        ]);
    }
}
