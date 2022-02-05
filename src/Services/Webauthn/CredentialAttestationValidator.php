<?php

namespace LaravelWebauthn\Services\Webauthn;

use LaravelWebauthn\Exceptions\ResponseMismatchException;
use Psr\Http\Message\ServerRequestInterface;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialLoader;
use Webauthn\PublicKeyCredentialSource;

class CredentialAttestationValidator
{
    /**
     * @var ServerRequestInterface
     */
    protected $serverRequest;

    /**
     * @var PublicKeyCredentialLoader
     */
    protected $publicKeyCredentialLoader;

    /**
     * @var AuthenticatorAttestationResponseValidator
     */
    protected $authenticatorAttestationResponseValidator;

    public function __construct(ServerRequestInterface $serverRequest, PublicKeyCredentialLoader $publicKeyCredentialLoader, AuthenticatorAttestationResponseValidator $authenticatorAttestationResponseValidator)
    {
        $this->serverRequest = $serverRequest;
        $this->publicKeyCredentialLoader = $publicKeyCredentialLoader;
        $this->authenticatorAttestationResponseValidator = $authenticatorAttestationResponseValidator;
    }

    /**
     * Validate a creation request.
     *
     * @param  PublicKeyCredentialCreationOptions  $publicKeyCredentialCreationOptions
     * @param  string  $data
     * @return PublicKeyCredentialSource
     *
     * @throws ResponseMismatchException
     */
    public function __invoke(PublicKeyCredentialCreationOptions $publicKeyCredentialCreationOptions, string $data): PublicKeyCredentialSource
    {
        // Load the data
        $publicKeyCredential = $this->publicKeyCredentialLoader->load($data);

        $response = $publicKeyCredential->getResponse();

        // Check if the response is an Authenticator Attestation Response
        if (! $response instanceof AuthenticatorAttestationResponse) {
            throw new ResponseMismatchException('Not an authenticator attestation response');
        }

        // Check the response against the request
        return $this->authenticatorAttestationResponseValidator->check(
            $response,
            $publicKeyCredentialCreationOptions,
            $this->serverRequest
        );
    }
}
