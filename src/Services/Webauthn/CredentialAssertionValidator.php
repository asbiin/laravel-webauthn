<?php

namespace LaravelWebauthn\Services\Webauthn;

use Illuminate\Contracts\Auth\Authenticatable as User;
use LaravelWebauthn\Exceptions\ResponseMismatchException;
use Psr\Http\Message\ServerRequestInterface;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\PublicKeyCredentialLoader;
use Webauthn\PublicKeyCredentialRequestOptions;

class CredentialAssertionValidator
{
    /**
     * @var ServerRequestInterface
     */
    protected $serverRequest;

    /**
     * @var PublicKeyCredentialLoader
     */
    protected $loader;

    /**
     * @var AuthenticatorAssertionResponseValidator
     */
    protected $validator;

    public function __construct(ServerRequestInterface $serverRequest, PublicKeyCredentialLoader $loader, AuthenticatorAssertionResponseValidator $validator)
    {
        $this->serverRequest = $serverRequest;
        $this->loader = $loader;
        $this->validator = $validator;
    }

    /**
     * Validate an authentication request.
     *
     * @param  User  $user
     * @param  PublicKeyCredentialRequestOptions  $requestOptions
     * @param  string  $data
     * @return bool
     *
     * @throws ResponseMismatchException
     */
    public function __invoke(User $user, PublicKeyCredentialRequestOptions $requestOptions, string $data): bool
    {
        // Load the data
        $publicKeyCredentials = $this->loader->load($data);

        $response = $publicKeyCredentials->getResponse();

        // Check if the response is an Authenticator Assertion Response
        if (! $response instanceof AuthenticatorAssertionResponse) {
            throw new ResponseMismatchException('Not an authenticator assertion response');
        }

        // Check the response against the request
        $this->validator->check(
            $publicKeyCredentials->getRawId(),
            $response,
            $requestOptions,
            $this->serverRequest,
            $user->getAuthIdentifier()
        );

        return true;
    }
}
