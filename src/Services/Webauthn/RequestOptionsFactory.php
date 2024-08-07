<?php

namespace LaravelWebauthn\Services\Webauthn;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Request;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;

final class RequestOptionsFactory extends OptionsFactory
{
    /**
     * User verification preference.
     */
    protected ?string $userVerification;

    public function __construct(
        Request $request,
        Cache $cache,
        Config $config,
        CredentialRepository $repository,
        protected PublicKeyCredentialRpEntity $publicKeyCredentialRpEntity
    ) {
        parent::__construct($request, $cache, $config, $repository);
        $this->userVerification = self::getUserVerification($config);
    }

    /**
     * Create a new PublicKeyCredentialCreationOptions object.
     */
    public function __invoke(?User $user): PublicKeyCredentialRequestOptions
    {
        $publicKey = new PublicKeyCredentialRequestOptions(
            $this->getChallenge(),
            $this->getRpId(),
            $this->getAllowedCredentials($user),
            $this->userVerification,
            $this->timeout
        );

        $value = json_encode($publicKey, flags: JSON_THROW_ON_ERROR);

        $this->cache->put($this->cacheKey($user), $value, $this->timeout);

        return $publicKey;
    }

    /**
     * Get user verification preference.
     */
    private static function getUserVerification(Config $config): ?string
    {
        return in_array($config->get('webauthn.userless'), ['required', 'preferred'], true)
            ? 'required'
            : $config->get('webauthn.user_verification', 'preferred');
    }

    /**
     * Get the list of allowed keys.
     *
     * @return array<array-key,PublicKeyCredentialDescriptor>
     */
    private function getAllowedCredentials(?User $user): array
    {
        return $user !== null ? CredentialRepository::getRegisteredKeys($user) : [];
    }

    /**
     * Get the rpEntity Id.
     */
    private function getRpId(): ?string
    {
        return $this->publicKeyCredentialRpEntity->id;
    }
}
