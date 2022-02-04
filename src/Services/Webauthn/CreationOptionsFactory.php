<?php

namespace LaravelWebauthn\Services\Webauthn;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;
use Cose\Algorithm\Manager as CoseAlgorithmManager;
use Illuminate\Contracts\Config\Repository as Config;
use Webauthn\PublicKeyCredentialSourceRepository;

final class CreationOptionsFactory extends OptionsFactory
{
    /**
     * @var PublicKeyCredentialRpEntity
     */
    protected $publicKeyCredentialRpEntity;

    /**
     * @var AuthenticatorSelectionCriteria
     */
    protected $authenticatorSelectionCriteria;

    /**
     * @var CoseAlgorithmManager
     */
    protected $algorithmManager;

    /**
     * Attestation Conveyance preference.
     * @var string
     */
    protected $attestationConveyance;

    public function __construct(Config $config, PublicKeyCredentialSourceRepository $repository, PublicKeyCredentialRpEntity $publicKeyCredentialRpEntity, AuthenticatorSelectionCriteria $authenticatorSelectionCriteria, CoseAlgorithmManager $algorithmManager)
    {
        parent::__construct($config, $repository);
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
        return (new PublicKeyCredentialCreationOptions(
            $this->publicKeyCredentialRpEntity,
            $this->getUserEntity($user),
            $this->getChallenge(),
            $this->createCredentialParameters(),
            $this->timeout
        ))
            ->excludeCredentials($this->getExcludedCredentials($user))
            ->setAuthenticatorSelection($this->authenticatorSelectionCriteria)
            ->setAttestation($this->attestationConveyance);
    }

    /**
     * Return the credential user entity.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     *
     * @return PublicKeyCredentialUserEntity
     */
    private function getUserEntity(User $user): PublicKeyCredentialUserEntity
    {
        return new PublicKeyCredentialUserEntity(
            $user->email ?? '',
            $user->getAuthIdentifier(),
            $user->email ?? '',
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
