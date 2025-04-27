<?php

namespace LaravelWebauthn\Services\Webauthn;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Http\Request;
use LaravelWebauthn\Exceptions\ResponseMismatchException;
use LaravelWebauthn\Services\Webauthn;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Symfony\Component\Serializer\SerializerInterface;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialRequestOptions;

class CredentialAssertionValidator extends CredentialValidator
{
    public function __construct(
        Request $request,
        Cache $cache,
        protected SerializerInterface $loader,
        protected AuthenticatorAssertionResponseValidator $validator
    ) {
        parent::__construct($request, $cache);
    }

    /**
     * Validate an authentication request.
     *
     * @throws ResponseMismatchException
     */
    public function __invoke(?User $user, array $data): bool
    {
        // Load the data
        $content = json_encode($data, flags: JSON_THROW_ON_ERROR);
        $publicKeyCredential = $this->loader->deserialize($content, PublicKeyCredential::class, 'json');

        // Check the response against the request
        $this->validator->check(
            $this->getCredentialSource($user, $publicKeyCredential),
            $this->getResponse($publicKeyCredential),
            $this->pullPublicKey($user),
            $this->request->host(),
            optional($user)->getAuthIdentifier()
        );

        return true;
    }

    /**
     * Get public Key credential.
     */
    protected function pullPublicKey(?User $user): PublicKeyCredentialRequestOptions
    {
        try {
            $value = $this->cache->pull($this->cacheKey($user));

            if ($value === null && Webauthn::userless()) {
                $value = $this->cache->pull($this->cacheKey(null));
            }

            if ($value === null) {
                abort(404, 'No public key credential found');
            }

            return $this->loader->deserialize($value, PublicKeyCredentialRequestOptions::class, 'json');
        } catch (\Exception $e) {
            app('webauthn.log')->debug('Webauthn publickKey deserialize error', ['exception' => $e]);
            abort(404, $e->getMessage());
        }
    }

    /**
     * Get authenticator response.
     */
    protected function getResponse(PublicKeyCredential $publicKeyCredential): AuthenticatorAssertionResponse
    {
        // Check if the response is an Authenticator Assertion Response
        if (! $publicKeyCredential->response instanceof AuthenticatorAssertionResponse) {
            throw new ResponseMismatchException('Not an authenticator attestation response');
        }

        return $publicKeyCredential->response;
    }

    /**
     * Get credential source from user and public key.
     */
    protected function getCredentialSource(?User $user, PublicKeyCredential $publicKeyCredential)
    {
        $credentialId = $publicKeyCredential->rawId;

        return (Webauthn::model())::where(
            fn ($query) => $query->where('credentialId', Base64UrlSafe::encode($credentialId))
                ->orWhere('credentialId', Base64UrlSafe::encodeUnpadded($credentialId))
        )->where(
            fn ($query) => $user !== null ? $query->where('user_id', $user->getAuthIdentifier()) : $query
        )
            ->firstOrFail()
            ->publicKeyCredentialSource;
    }
}
