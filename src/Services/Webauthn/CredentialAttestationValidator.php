<?php

namespace LaravelWebauthn\Services\Webauthn;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Http\Request;
use LaravelWebauthn\Exceptions\ResponseMismatchException;
use Symfony\Component\Serializer\SerializerInterface;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialSource;

class CredentialAttestationValidator extends CredentialValidator
{
    public function __construct(
        Request $request,
        Cache $cache,
        protected SerializerInterface $loader,
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
        $content = json_encode($data, flags: JSON_THROW_ON_ERROR);
        $publicKeyCredential = $this->loader->deserialize($content, PublicKeyCredential::class, 'json');

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
            $value = $this->cache->pull($this->cacheKey($user));

            if ($value === null) {
                abort(404, 'No public key credential found');
            }

            return $this->loader->deserialize($value, PublicKeyCredentialCreationOptions::class, 'json');
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
