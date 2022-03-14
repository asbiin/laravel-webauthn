<?php

namespace LaravelWebauthn\Services\Webauthn;

use Cose\Algorithm\Manager as CoseAlgorithmManager;
use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Request;
use LaravelWebauthn\Facades\Webauthn;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialSourceRepository;
use Webauthn\PublicKeyCredentialUserEntity;

final class CreationOptionsFactory extends OptionsFactory
{
    /**
     * @var PublicKeyCredentialRpEntity
     */
    protected PublicKeyCredentialRpEntity $publicKeyCredentialRpEntity;

    /**
     * @var AuthenticatorSelectionCriteria
     */
    protected AuthenticatorSelectionCriteria $authenticatorSelectionCriteria;

    /**
     * @var CoseAlgorithmManager
     */
    protected CoseAlgorithmManager $algorithmManager;

    /**
     * Attestation Conveyance preference.
     *
     * @var string
     */
    protected string $attestationConveyance;

    public function __construct(Request $request, Cache $cache, Config $config, PublicKeyCredentialSourceRepository $repository, PublicKeyCredentialRpEntity $publicKeyCredentialRpEntity, AuthenticatorSelectionCriteria $authenticatorSelectionCriteria, CoseAlgorithmManager $algorithmManager)
    {
        parent::__construct($request, $cache, $config, $repository);
        $this->publicKeyCredentialRpEntity = $publicKeyCredentialRpEntity;
        $this->authenticatorSelectionCriteria = $authenticatorSelectionCriteria;
        $this->algorithmManager = $algorithmManager;
        $this->attestationConveyance = $config->get('webauthn.attestation_conveyance', 'none');
    }

    /**
     * Create a new PublicKeyCredentialCreationOptions object.
     *
     * @param  User  $user
     * @return PublicKeyCredentialCreationOptions
     */
    public function __invoke(User $user): PublicKeyCredentialCreationOptions
    {
        $publicKey = (new PublicKeyCredentialCreationOptions(
            $this->publicKeyCredentialRpEntity,
            $this->getUserEntity($user),
            $this->getChallenge(),
            $this->createCredentialParameters(),
            $this->timeout
        ))
            ->excludeCredentials($this->getExcludedCredentials($user))
            ->setAuthenticatorSelection($this->authenticatorSelectionCriteria)
            ->setAttestation($this->attestationConveyance);

        $this->cache->put($this->cacheKey($user), $publicKey, $this->timeout);

        return $publicKey;
    }

    /**
     * Return the credential user entity.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return PublicKeyCredentialUserEntity
     */
    private function getUserEntity(User $user): PublicKeyCredentialUserEntity
    {
        return new PublicKeyCredentialUserEntity(
            $user->{Webauthn::username()} ?? '',
            $user->getAuthIdentifier(),
            $user->{Webauthn::username()} ?? '',
            null
        );
    }

    /**
     * @return PublicKeyCredentialParameters[]
     */
    private function createCredentialParameters(): array
    {
        return collect($this->algorithmManager->list())
            ->map(function ($algorithm): PublicKeyCredentialParameters {
                return new PublicKeyCredentialParameters(
                    PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
                    $algorithm
                );
            })
            ->toArray();
    }

    /**
     * Get the excluded credentials.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return array
     */
    protected function getExcludedCredentials(User $user): array
    {
        return $this->repository->getRegisteredKeys($user);
    }
}
