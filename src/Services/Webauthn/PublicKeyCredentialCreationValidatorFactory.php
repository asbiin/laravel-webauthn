<?php

namespace LaravelWebauthn\Services\Webauthn;

use CBOR\Decoder;
use Webauthn\PublicKeyCredentialSource;
use Zend\Diactoros\ServerRequestFactory;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialSourceRepository;
use Webauthn\AuthenticatorAttestationResponseValidator;
use LaravelWebauthn\Exceptions\ResponseMismatchException;
use Webauthn\TokenBinding\TokenBindingNotSupportedHandler;
use Webauthn\AuthenticationExtensions\ExtensionOutputCheckerHandler;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;

final class PublicKeyCredentialCreationValidatorFactory extends AbstractValidator
{
    /**
     * @return PublicKeyCredentialSource
     * @throws ResponseMismatchException
     */
    public function validate(PublicKeyCredentialCreationOptions $publicKeyCredentialCreationOptions, string $data): PublicKeyCredentialSource
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
        $authenticatorAttestationResponseValidator = $this->getAuthenticatorAttestationResponseValidator($this->credentialRepository, $attestationStatementSupportManager);

        // Check the response against the request
        $authenticatorAttestationResponseValidator->check(
            $response,
            $publicKeyCredentialCreationOptions,
            ServerRequestFactory::fromGlobals()
        );

        // Everything is OK here. You can get the PublicKeyCredentialDescriptor.
        return PublicKeyCredentialSource::createFromPublicKeyCredential(
            $publicKeyCredential,
            $publicKeyCredentialCreationOptions->getUser()->getId()
        );
    }

    /**
     * Get the Authenticator Attestation Response Validator.
     * @param AttestationStatementSupportManager $attestationStatementSupportManager
     * @return AuthenticatorAttestationResponseValidator
     */
    private function getAuthenticatorAttestationResponseValidator(PublicKeyCredentialSourceRepository $credentialRepository, AttestationStatementSupportManager $attestationStatementSupportManager) : AuthenticatorAttestationResponseValidator
    {
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
