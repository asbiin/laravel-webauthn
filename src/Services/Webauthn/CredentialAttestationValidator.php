<?php

namespace LaravelWebauthn\Services\Webauthn;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Http\Request;
use LaravelWebauthn\Exceptions\ResponseMismatchException;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialLoader;
use Webauthn\PublicKeyCredentialSource;

class CredentialAttestationValidator extends CredentialValidator
{
    public function __construct(
        Request $request,
        Cache $cache,
        protected PublicKeyCredentialLoader $loader,
        protected AuthenticatorAttestationResponseValidator $validator
    ) {
        parent::__construct($request, $cache);
    }

    /**
     * Validate a creation request.
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
            $this->request->host()
        );
    }

    /**
     * Get public Key credential.
     */
    protected function pullPublicKey(User $user): PublicKeyCredentialCreationOptions
    {
        try {
            $value = json_decode($this->cache->pull($this->cacheKey($user)), true, flags: JSON_THROW_ON_ERROR);

            return PublicKeyCredentialCreationOptions::createFromArray($value);
        } catch (\Exception $e) {
            app('webauthn.log')->debug('Webauthn publicKey deserialize error', ['exception' => $e]);
            abort(404);
        }
    }

    /**
     * Get authenticator response.
     */
    protected function getResponse(PublicKeyCredential $publicKeyCredential): AuthenticatorAttestationResponse
    {
        // Check if the response is an Authenticator Attestation Response
        if (! $publicKeyCredential->response instanceof AuthenticatorAttestationResponse) {
            throw new ResponseMismatchException('Not an authenticator attestation response');
        }

        return $publicKeyCredential->response;
    }
}
