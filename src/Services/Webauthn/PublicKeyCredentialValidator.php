<?php

namespace LaravelWebauthn\Services\Webauthn;

use Cose\Algorithm\Manager;
use Cose\Algorithm\Signature;
use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Contracts\Container\BindingResolutionException;
use LaravelWebauthn\Exceptions\ResponseMismatchException;
use Psr\Http\Message\ServerRequestInterface;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AuthenticationExtensions\ExtensionOutputCheckerHandler;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\TokenBinding\TokenBindingNotSupportedHandler;

class PublicKeyCredentialValidator extends AbstractValidatorFactory
{
    /**
     * Validate a creation request.
     *
     * @param  PublicKeyCredentialCreationOptions  $publicKeyCredentialCreationOptions
     * @param  string  $data
     * @return PublicKeyCredentialSource
     *
     * @throws ResponseMismatchException
     */
    public function validate(PublicKeyCredentialCreationOptions $publicKeyCredentialCreationOptions, string $data): PublicKeyCredentialSource
    {
        $coseAlgorithmManager = $this->getCoseAlgorithmManager();

        $attestationStatementSupportManager = $this->getAttestationStatementSupportManager($coseAlgorithmManager);

        // Public Key Credential Loader
        $publicKeyCredentialLoader = $this->getPublicKeyCredentialLoader($attestationStatementSupportManager);

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
        return $authenticatorAttestationResponseValidator->check(
            $response,
            $publicKeyCredentialCreationOptions,
            $this->getServerRequestInterface()
        );
    }

    /**
     * Validate an authentication request.
     *
     * @param  User  $user
     * @param  PublicKeyCredentialRequestOptions  $publicKeyCredentialRequestOptions
     * @param  string  $data
     * @return bool
     *
     * @throws ResponseMismatchException
     */
    public function check(User $user, PublicKeyCredentialRequestOptions $publicKeyCredentialRequestOptions, string $data): bool
    {
        $coseAlgorithmManager = $this->getCoseAlgorithmManager();

        $attestationStatementSupportManager = $this->getAttestationStatementSupportManager($coseAlgorithmManager);

        // Public Key Credential Loader
        $publicKeyCredentialLoader = $this->getPublicKeyCredentialLoader($attestationStatementSupportManager);

        // Load the data
        $publicKeyCredential = $publicKeyCredentialLoader->load($data);

        $response = $publicKeyCredential->getResponse();

        // Check if the response is an Authenticator Assertion Response
        if (! $response instanceof AuthenticatorAssertionResponse) {
            throw new ResponseMismatchException('Not an authenticator assertion response');
        }

        // Authenticator Assertion Response Validator
        $authenticatorAssertionResponseValidator = $this->getAuthenticatorAssertionResponseValidator($coseAlgorithmManager);

        // Check the response against the request
        $authenticatorAssertionResponseValidator->check(
            $publicKeyCredential->getRawId(),
            $response,
            $publicKeyCredentialRequestOptions,
            $this->getServerRequestInterface(),
            $user->getAuthIdentifier()
        );

        return true;
    }

    /**
     * Get the Authenticator Assertion Response Validator.
     *
     * @param  Manager  $coseAlgorithmManager
     * @return AuthenticatorAssertionResponseValidator
     */
    private function getAuthenticatorAssertionResponseValidator(Manager $coseAlgorithmManager): AuthenticatorAssertionResponseValidator
    {
        // The token binding handler
        $tokenBindingHandler = new TokenBindingNotSupportedHandler();

        $extensionOutputCheckerHandler = new ExtensionOutputCheckerHandler();

        // Authenticator Attestation Response Validator
        return new AuthenticatorAssertionResponseValidator(
            $this->repository,
            $tokenBindingHandler,
            $extensionOutputCheckerHandler,
            $coseAlgorithmManager
        );
    }

    /**
     * Get the Authenticator Attestation Response Validator.
     *
     * @param  AttestationStatementSupportManager  $attestationStatementSupportManager
     * @return AuthenticatorAttestationResponseValidator
     */
    private function getAuthenticatorAttestationResponseValidator(AttestationStatementSupportManager $attestationStatementSupportManager): AuthenticatorAttestationResponseValidator
    {
        // The token binding handler
        $tokenBindingHandler = new TokenBindingNotSupportedHandler();

        $extensionOutputCheckerHandler = new ExtensionOutputCheckerHandler();

        // Authenticator Attestation Response Validator
        return new AuthenticatorAttestationResponseValidator(
            $attestationStatementSupportManager,
            $this->repository,
            $tokenBindingHandler,
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

    private function getServerRequestInterface(): ServerRequestInterface
    {
        if (class_exists(\GuzzleHttp\Psr7\ServerRequest::class)) {
            return \GuzzleHttp\Psr7\ServerRequest::fromGlobals();
        } elseif (class_exists(\Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory::class)) {
            if (class_exists(\Nyholm\Psr7\Factory\Psr17Factory::class)) {
                return app(ServerRequestInterface::class);
            } elseif (class_exists(\Http\Discovery\Psr17FactoryDiscovery::class)) {
                $uploadFileFactory = \Http\Discovery\Psr17FactoryDiscovery::findUploadedFileFactory();
                $responseFactory = \Http\Discovery\Psr17FactoryDiscovery::findResponseFactory();
                $serverRequestFactory = \Http\Discovery\Psr17FactoryDiscovery::findServerRequestFactory();
                $streamFactory = \Http\Discovery\Psr17FactoryDiscovery::findStreamFactory();

                return (new \Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory($serverRequestFactory, $streamFactory, $uploadFileFactory, $responseFactory))
                        ->createRequest(app('request'));
            }
        }

        throw new BindingResolutionException('Unable to resolve PSR request. Please install the guzzlehttp/psr7 or symfony/psr-http-message-bridge and a psr/http-factory-implementation implementation.');
    }
}
