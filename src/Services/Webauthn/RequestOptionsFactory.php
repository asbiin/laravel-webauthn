<?php

namespace LaravelWebauthn\Services\Webauthn;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Request;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialSourceRepository;

final class RequestOptionsFactory extends OptionsFactory
{
    /**
     * User verification preference.
     *
     * @var string|null
     */
    protected ?string $userVerification;

    /**
     * @var PublicKeyCredentialRpEntity
     */
    protected PublicKeyCredentialRpEntity $publicKeyCredentialRpEntity;

    public function __construct(Request $request, Cache $cache, Config $config, PublicKeyCredentialSourceRepository $repository, PublicKeyCredentialRpEntity $publicKeyCredentialRpEntity)
    {
        parent::__construct($request, $cache, $config, $repository);
        $this->publicKeyCredentialRpEntity = $publicKeyCredentialRpEntity;
        $this->userVerification = self::getUserVerification($config);
    }

    /**
     * Create a new PublicKeyCredentialCreationOptions object.
     *
     * @param  User  $user
     * @return PublicKeyCredentialRequestOptions
     */
    public function __invoke(User $user): PublicKeyCredentialRequestOptions
    {
        $publicKey = (new PublicKeyCredentialRequestOptions($this->getChallenge()))
            ->setTimeout($this->timeout)
            ->allowCredentials(...$this->getAllowedCredentials($user))
            ->setRpId($this->getRpId())
            ->setUserVerification($this->userVerification);

        $this->cache->put($this->cacheKey($user), $publicKey, $this->timeout);

        return $publicKey;
    }

    /**
     * Get user verification preference.
     *
     * @param  \Illuminate\Contracts\Config\Repository  $config
     * @return string|null
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
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return array
     */
    private function getAllowedCredentials(User $user): array
    {
        return $this->repository->getRegisteredKeys($user);
    }

    /**
     * Get the rpEntity Id.
     *
     * @return string|null
     */
    private function getRpId(): ?string
    {
        return $this->publicKeyCredentialRpEntity->getId();
    }
}
