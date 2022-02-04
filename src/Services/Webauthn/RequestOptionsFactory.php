<?php

namespace LaravelWebauthn\Services\Webauthn;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Webauthn\PublicKeyCredentialRequestOptions;
use Illuminate\Contracts\Config\Repository as Config;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialSourceRepository;

final class RequestOptionsFactory extends OptionsFactory
{
    /**
     * User verification preference.
     *
     * @var string|null
     */
    protected $userVerification;

    /**
     * @var PublicKeyCredentialRpEntity
     */
    protected $publicKeyCredentialRpEntity;

    public function __construct(Config $config, PublicKeyCredentialSourceRepository $repository, PublicKeyCredentialRpEntity $publicKeyCredentialRpEntity)
    {
        parent::__construct($config, $repository);
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
        return (new PublicKeyCredentialRequestOptions(
            $this->getChallenge(),
            $this->timeout
        ))
            ->allowCredentials($this->getAllowedCredentials($user))
            ->setRpId($this->getRpId())
            ->setUserVerification($this->userVerification);
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
