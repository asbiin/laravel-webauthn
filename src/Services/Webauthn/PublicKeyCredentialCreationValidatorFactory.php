<?php

namespace LaravelWebauthn\Services\Webauthn;

use CBOR\Decoder;
use Zend\Diactoros\ServerRequestFactory;
use Illuminate\Contracts\Config\Repository;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\AuthenticatorAttestationResponseValidator;
use LaravelWebauthn\Exceptions\ResponseMismatchException;
use Webauthn\TokenBinding\TokenBindingNotSupportedHandler;
use Webauthn\AuthenticationExtensions\ExtensionOutputCheckerHandler;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;

final class PublicKeyCredentialCreationValidatorFactory extends AbstractValidator
{
    /**
     * @throws ResponseMismatchException
     */
    public function validate(PublicKeyCredentialCreationOptions $publicKeyCredentialCreationOptions, string $data): array
    {
        // Create a CBOR Decoder object
        $decoder = $this->createCBORDecoder();

        $attestationStatementSupportManager = $this->getAttestationStatementSupportManager($decoder);

        // Public Key Credential Loader
        $publicKeyCredentialLoader = $this->getPublicKeyCredentialLoader($attestationStatementSupportManager, $decoder);

        // Load the data
        $publicKeyCredential = $publicKeyCredentialLoader->load($data);

        $response = $publicKeyCredential->getResponse();

        // Check if the response is an Authenticator Attestation Response
        if (! $response instanceof AuthenticatorAttestationResponse) {
            throw new ResponseMismatchException('Not an authenticator attestation response');
        }

        // Authenticator Attestation Response Validator
        $authenticatorAttestationResponseValidator = $this->getAuthenticatorAttestationResponseValidator($attestationStatementSupportManager);

        // Check the response against the request
        $authenticatorAttestationResponseValidator->check(
            $response,
            $publicKeyCredentialCreationOptions,
            ServerRequestFactory::fromGlobals()
        );

        // Everything is OK here. You can get the PublicKeyCredentialDescriptor.
        $publicKeyCredentialDescriptor = $publicKeyCredential->getPublicKeyCredentialDescriptor();

        // Normally this condition should be true. Just make sure you received the credential data
        $attestedCredentialData = null;
        if ($response->getAttestationObject()->getAuthData()->hasAttestedCredentialData()) {
            $attestedCredentialData = $response->getAttestationObject()->getAuthData()->getAttestedCredentialData();
        }

        return [
            $publicKeyCredentialDescriptor,
            $attestedCredentialData,
        ];
    }

    /**
     * Get the Authenticator Attestation Response Validator.
     * @param AttestationStatementSupportManager $attestationStatementSupportManager
     * @return AuthenticatorAttestationResponseValidator
     */
    private function getAuthenticatorAttestationResponseValidator(AttestationStatementSupportManager $attestationStatementSupportManager) : AuthenticatorAttestationResponseValidator
    {
        // Credential Repository
        $credentialRepository = new CredentialRepository();

        // The token binding handler
        $tokenBindnigHandler = new TokenBindingNotSupportedHandler();

        $extensionOutputCheckerHandler = new ExtensionOutputCheckerHandler();

        // Authenticator Attestation Response Validator
        return new AuthenticatorAttestationResponseValidator(
            $attestationStatementSupportManager,
            $credentialRepository,
            $tokenBindnigHandler,
            $extensionOutputCheckerHandler
        );
    }
}
