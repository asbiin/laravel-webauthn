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
use Webauthn\PublicKeyCredentialUserEntity;

final class CreationOptionsFactory extends OptionsFactory
{
    /**
     * Attestation Conveyance preference.
     */
    protected string $attestationConveyance;

    public function __construct(
        Request $request,
        Cache $cache,
        Config $config,
        CredentialRepository $repository,
        protected PublicKeyCredentialRpEntity $publicKeyCredentialRpEntity,
        protected AuthenticatorSelectionCriteria $authenticatorSelectionCriteria,
        protected CoseAlgorithmManager $algorithmManager
    ) {
        parent::__construct($request, $cache, $config, $repository);
        $this->attestationConveyance = $config->get('webauthn.attestation_conveyance', 'none');
    }

    /**
     * Create a new PublicKeyCredentialCreationOptions object.
     */
    public function __invoke(User $user): PublicKeyCredentialCreationOptions
    {
        $publicKey = (new PublicKeyCredentialCreationOptions(
            $this->publicKeyCredentialRpEntity,
            $this->getUserEntity($user),
            $this->getChallenge(),
            $this->createCredentialParameters()
        ))
            ->setTimeout($this->timeout)
            ->excludeCredentials(...$this->getExcludedCredentials($user))
            ->setAuthenticatorSelection($this->authenticatorSelectionCriteria)
            ->setAttestation($this->attestationConveyance);

        $this->cache->put($this->cacheKey($user), $publicKey->jsonSerialize(), $this->timeout);

        return $publicKey;
    }

    /**
     * Return the credential user entity.
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
     * @return array<array-key,PublicKeyCredentialParameters>
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
     */
    protected function getExcludedCredentials(User $user): array
    {
        return CredentialRepository::getRegisteredKeys($user);
    }
}
