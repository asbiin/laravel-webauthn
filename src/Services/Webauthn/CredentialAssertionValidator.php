<?php

namespace LaravelWebauthn\Services\Webauthn;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Http\Request;
use LaravelWebauthn\Exceptions\ResponseMismatchException;
use LaravelWebauthn\Services\Webauthn;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialLoader;
use Webauthn\PublicKeyCredentialRequestOptions;

class CredentialAssertionValidator extends CredentialValidator
{
    public function __construct(
        Request $request,
        Cache $cache,
        protected PublicKeyCredentialLoader $loader,
        protected AuthenticatorAssertionResponseValidator $validator
    ) {
        parent::__construct($request, $cache);
    }

    /**
     * Validate an authentication request.
     *
     * @throws ResponseMismatchException
     */
    public function __invoke(User $user, array $data): bool
    {
        // Load the data
        $publicKeyCredential = $this->loader->loadArray($data);

        // Check the response against the request
        $this->validator->check(
            $this->getCredentialSource($user, $publicKeyCredential),
            $this->getResponse($publicKeyCredential),
            $this->pullPublicKey($user),
            $this->request->host(),
            $user->getAuthIdentifier()
        );

        return true;
    }

    /**
     * Get public Key credential.
     */
    protected function pullPublicKey(User $user): PublicKeyCredentialRequestOptions
    {
        try {
            return PublicKeyCredentialRequestOptions::createFromArray($this->cache->pull($this->cacheKey($user)));
        } catch (\Exception $e) {
            app('webauthn.log')->debug('Webauthn publickKey deserialize error', ['exception' => $e]);
            abort(404);
        }
    }

    /**
     * Get authenticator response.
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

    /**
     * Get credential source from user and public key.
     */
    protected function getCredentialSource(User $user, PublicKeyCredential $publicKeyCredential)
    {
        $credentialId = $publicKeyCredential->getRawId();

        return (Webauthn::model())::where('user_id', $user->getAuthIdentifier())
            ->where(fn ($query) => $query->where('credentialId', Base64UrlSafe::encode($credentialId))
                    ->orWhere('credentialId', Base64UrlSafe::encodeUnpadded($credentialId))
            )
            ->firstOrFail()
            ->publicKeyCredentialSource;
    }
}
