<?php

namespace LaravelWebauthn\Services\Webauthn;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use LaravelWebauthn\Exceptions\ResponseMismatchException;
use Psr\Http\Message\ServerRequestInterface;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialLoader;
use Webauthn\PublicKeyCredentialRequestOptions;

class CredentialAssertionValidator extends CredentialValidator
{
    /**
     * @var ServerRequestInterface
     */
    protected ServerRequestInterface $serverRequest;

    /**
     * @var PublicKeyCredentialLoader
     */
    protected PublicKeyCredentialLoader $loader;

    /**
     * @var AuthenticatorAssertionResponseValidator
     */
    protected AuthenticatorAssertionResponseValidator $validator;

    public function __construct(Request $request, Cache $cache, ServerRequestInterface $serverRequest, PublicKeyCredentialLoader $loader, AuthenticatorAssertionResponseValidator $validator)
    {
        parent::__construct($request, $cache);
        $this->serverRequest = $serverRequest;
        $this->loader = $loader;
        $this->validator = $validator;
    }

    /**
     * Validate an authentication request.
     *
     * @param  User  $user
     * @param  array  $data
     * @return bool
     *
     * @throws ResponseMismatchException
     */
    public function __invoke(User $user, array $data): bool
    {
        // Load the data
        $publicKeyCredential = $this->loader->loadArray($data);

        // Check the response against the request
        $this->validator->check(
            $publicKeyCredential->getRawId(),
            $this->getResponse($publicKeyCredential),
            $this->pullPublicKey($user),
            $this->serverRequest,
            $user->getAuthIdentifier()
        );

        return true;
    }

    /**
     * Get public Key credential.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return \Webauthn\PublicKeyCredentialRequestOptions
     */
    protected function pullPublicKey(User $user): PublicKeyCredentialRequestOptions
    {
        return tap($this->cache->pull($this->cacheKey($user)), function ($publicKey) {
            if (! $publicKey instanceof PublicKeyCredentialRequestOptions) {
                Log::debug('Webauthn wrong publickKey type');
                abort(404);
            }
        });
    }

    /**
     * Get authenticator response.
     *
     * @param  \Webauthn\PublicKeyCredential  $publicKeyCredential
     * @return \Webauthn\AuthenticatorAssertionResponse
     */
    protected function getResponse(PublicKeyCredential $publicKeyCredential): AuthenticatorAssertionResponse
    {
        $response = $publicKeyCredential->getResponse();

        // Check if the response is an Authenticator Assertion Response
        if (! $response instanceof AuthenticatorAssertionResponse) {
            throw new ResponseMismatchException('Not an authenticator attestation response');
        }

        return $response;
    }
}
