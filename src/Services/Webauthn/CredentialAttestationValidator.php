<?php

namespace LaravelWebauthn\Services\Webauthn;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use LaravelWebauthn\Exceptions\ResponseMismatchException;
use Psr\Http\Message\ServerRequestInterface;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialLoader;
use Webauthn\PublicKeyCredentialSource;

class CredentialAttestationValidator extends CredentialValidator
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
     * @var AuthenticatorAttestationResponseValidator
     */
    protected AuthenticatorAttestationResponseValidator $validator;

    public function __construct(Request $request, Cache $cache, ServerRequestInterface $serverRequest, PublicKeyCredentialLoader $loader, AuthenticatorAttestationResponseValidator $validator)
    {
        parent::__construct($request, $cache);
        $this->serverRequest = $serverRequest;
        $this->loader = $loader;
        $this->validator = $validator;
    }

    /**
     * Validate a creation request.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  array  $data
     * @return PublicKeyCredentialSource
     *
     * @throws ResponseMismatchException
     */
    public function __invoke(User $user, array $data): PublicKeyCredentialSource
    {
        // Load the data
        $publicKeyCredential = $this->loader->loadArray($data);

        // Check the response against the request
        return $this->validator->check(
            $this->getResponse($publicKeyCredential),
            $this->pullPublicKey($user),
            $this->serverRequest
        );
    }

    /**
     * Get public Key credential.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return \Webauthn\PublicKeyCredentialCreationOptions
     */
    protected function pullPublicKey(User $user): PublicKeyCredentialCreationOptions
    {
        return tap($this->cache->pull($this->cacheKey($user)), function ($publicKey) {
            if (! $publicKey instanceof PublicKeyCredentialCreationOptions) {
                Log::debug('Webauthn wrong publickKey type');
                abort(404);
            }
        });
    }

    /**
     * Get authenticator response.
     *
     * @param  \Webauthn\PublicKeyCredential  $publicKeyCredential
     * @return \Webauthn\AuthenticatorAttestationResponse
     */
    protected function getResponse(PublicKeyCredential $publicKeyCredential): AuthenticatorAttestationResponse
    {
        $response = $publicKeyCredential->getResponse();

        // Check if the response is an Authenticator Attestation Response
        if (! $response instanceof AuthenticatorAttestationResponse) {
            throw new ResponseMismatchException('Not an authenticator attestation response');
        }

        return $response;
    }
}
