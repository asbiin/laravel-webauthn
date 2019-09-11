<?php

namespace LaravelWebauthn\Services\Webauthn;

use CBOR\Decoder;
use Cose\Algorithm\Manager;
use Cose\Algorithm\Signature;
use GuzzleHttp\Psr7\ServerRequest;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Illuminate\Contracts\Auth\Authenticatable as User;
use Webauthn\AuthenticatorAttestationResponseValidator;
use LaravelWebauthn\Exceptions\ResponseMismatchException;
use Webauthn\TokenBinding\TokenBindingNotSupportedHandler;
use Webauthn\AuthenticationExtensions\ExtensionOutputCheckerHandler;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;

final class PublicKeyCredentialValidator extends AbstractValidatorFactory
{
    /**
     * Validate a creation request.
     *
     * @param PublicKeyCredentialCreationOptions $publicKeyCredentialCreationOptions
     * @param string $data
     * @return PublicKeyCredentialSource
     * @throws ResponseMismatchException
     */
    public function validate(PublicKeyCredentialCreationOptions $publicKeyCredentialCreationOptions, string $data): PublicKeyCredentialSource
    {
        // Create a CBOR Decoder object
        $decoder = $this->createCBORDecoder();

        $coseAlgorithmManager = $this->getCoseAlgorithmManager();

        $attestationStatementSupportManager = $this->getAttestationStatementSupportManager($decoder, $coseAlgorithmManager);

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
            ServerRequest::fromGlobals()
        );

        // Everything is OK here. You can get the PublicKeyCredentialDescriptor.
        return PublicKeyCredentialSource::createFromPublicKeyCredential(
            $publicKeyCredential,
            $publicKeyCredentialCreationOptions->getUser()->getId()
        );
    }

    /**
     * Validate an authentication request.
     *
     * @param User $user
     * @param PublicKeyCredentialRequestOptions $publicKeyCredentialRequestOptions
     * @param string $data
     * @return bool
     * @throws ResponseMismatchException
     */
    public function check(User $user, PublicKeyCredentialRequestOptions $publicKeyCredentialRequestOptions, string $data): bool
    {
        // Create a CBOR Decoder object
        $decoder = $this->createCBORDecoder();

        $coseAlgorithmManager = $this->getCoseAlgorithmManager();

        $attestationStatementSupportManager = $this->getAttestationStatementSupportManager($decoder, $coseAlgorithmManager);

        // Public Key Credential Loader
        $publicKeyCredentialLoader = $this->getPublicKeyCredentialLoader($attestationStatementSupportManager, $decoder);

        // Load the data
        $publicKeyCredential = $publicKeyCredentialLoader->load($data);

        $response = $publicKeyCredential->getResponse();

        // Check if the response is an Authenticator Assertion Response
        if (! $response instanceof AuthenticatorAssertionResponse) {
            throw new ResponseMismatchException('Not an authenticator assertion response');
        }

        // Authenticator Assertion Response Validator
        $authenticatorAssertionResponseValidator = $this->getAuthenticatorAssertionResponseValidator($decoder, $coseAlgorithmManager);

        // Check the response against the request
        $authenticatorAssertionResponseValidator->check(
            $publicKeyCredential->getRawId(),
            $response,
            $publicKeyCredentialRequestOptions,
            ServerRequest::fromGlobals(),
            $user->getAuthIdentifier()
        );

        return true;
    }

    /**
     * Get the Authenticator Assertion Response Validator.
     *
     * @param Decoder $decoder
     * @param Manager $coseAlgorithmManager
     * @return AuthenticatorAssertionResponseValidator
     */
    private function getAuthenticatorAssertionResponseValidator(Decoder $decoder, Manager $coseAlgorithmManager) : AuthenticatorAssertionResponseValidator
    {
        // The token binding handler
        $tokenBindnigHandler = new TokenBindingNotSupportedHandler();

        $extensionOutputCheckerHandler = new ExtensionOutputCheckerHandler();

        // Authenticator Attestation Response Validator
        return new AuthenticatorAssertionResponseValidator(
            $this->repository,
            $decoder,
            $tokenBindnigHandler,
            $extensionOutputCheckerHandler,
            $coseAlgorithmManager
        );
    }

    /**
     * Get the Authenticator Attestation Response Validator.
     *
     * @param AttestationStatementSupportManager $attestationStatementSupportManager
     * @return AuthenticatorAttestationResponseValidator
     */
    private function getAuthenticatorAttestationResponseValidator(AttestationStatementSupportManager $attestationStatementSupportManager) : AuthenticatorAttestationResponseValidator
    {
        // The token binding handler
        $tokenBindnigHandler = new TokenBindingNotSupportedHandler();

        $extensionOutputCheckerHandler = new ExtensionOutputCheckerHandler();

        // Authenticator Attestation Response Validator
        return new AuthenticatorAttestationResponseValidator(
            $attestationStatementSupportManager,
            $this->repository,
            $tokenBindnigHandler,
            $extensionOutputCheckerHandler
        );
    }

    /**
     * Get the Cose Algorithm Manager.
     *
     * @return Manager
     */
    private function getCoseAlgorithmManager()
    {
        $coseAlgorithmManager = new Manager();

        $coseAlgorithmManager->add(new Signature\ECDSA\ES256());
        $coseAlgorithmManager->add(new Signature\ECDSA\ES512());
        $coseAlgorithmManager->add(new Signature\EdDSA\EdDSA());
        $coseAlgorithmManager->add(new Signature\RSA\RS1());
        $coseAlgorithmManager->add(new Signature\RSA\RS256());
        $coseAlgorithmManager->add(new Signature\RSA\RS512());

        return $coseAlgorithmManager;
    }
}
