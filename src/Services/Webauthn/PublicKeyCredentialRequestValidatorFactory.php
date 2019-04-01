<?php

namespace LaravelWebauthn\Services\Webauthn;

use CBOR\Decoder;
use Zend\Diactoros\ServerRequestFactory;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialSourceRepository;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Illuminate\Contracts\Auth\Authenticatable as User;
use LaravelWebauthn\Exceptions\ResponseMismatchException;
use Webauthn\TokenBinding\TokenBindingNotSupportedHandler;
use Webauthn\AuthenticationExtensions\ExtensionOutputCheckerHandler;

final class PublicKeyCredentialRequestValidatorFactory extends AbstractValidator
{
    /**
     * @throws ResponseMismatchException
     */
    public function check(User $user, PublicKeyCredentialRequestOptions $publicKeyCredentialRequestOptions, string $data): bool
    {
        // Create a CBOR Decoder object
        $decoder = $this->createCBORDecoder();

        $attestationStatementSupportManager = $this->getAttestationStatementSupportManager($decoder);

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
        $authenticatorAssertionResponseValidator = $this->getAuthenticatorAttestationResponseValidator($this->credentialRepository, $decoder);

        // Check the response against the request
        $authenticatorAssertionResponseValidator->check(
            $publicKeyCredential->getRawId(),
            $response,
            $publicKeyCredentialRequestOptions,
            ServerRequestFactory::fromGlobals(),
            $user->getAuthIdentifier()
        );

        return true;
    }

    /**
     * Get the Authenticator Attestation Response Validator.
     *
     * @param PublicKeyCredentialSourceRepository $credentialRepository
     * @param Decoder $decoder
     * @return AuthenticatorAssertionResponseValidator
     */
    private function getAuthenticatorAttestationResponseValidator(PublicKeyCredentialSourceRepository $credentialRepository, Decoder $decoder) : AuthenticatorAssertionResponseValidator
    {
        // The token binding handler
        $tokenBindnigHandler = new TokenBindingNotSupportedHandler();

        $extensionOutputCheckerHandler = new ExtensionOutputCheckerHandler();

        // Authenticator Attestation Response Validator
        return new AuthenticatorAssertionResponseValidator(
            $credentialRepository,
            $decoder,
            $tokenBindnigHandler,
            $extensionOutputCheckerHandler
        );
    }
}
