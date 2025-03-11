<?php

namespace LaravelWebauthn\Services\Webauthn;

use Cose\Algorithm\Manager as CoseAlgorithmManager;
use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Request;
use LaravelWebauthn\Facades\Webauthn;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\PublicKeyCredentialCreationOptions as PublicKeyCredentialCreationOptionsBase;
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
        protected PublicKeyCredentialRpEntity $publicKeyCredentialRpEntity,
        protected AuthenticatorSelectionCriteria $authenticatorSelectionCriteria,
        protected CoseAlgorithmManager $algorithmManager
    ) {
        parent::__construct($request, $cache, $config);
        $this->attestationConveyance = $config->get('webauthn.attestation_conveyance', 'none');
    }

    /**
     * Create a new PublicKeyCredentialCreationOptions object.
     */
    public function __invoke(User $user): PublicKeyCredentialCreationOptions
    {
        $publicKey = new PublicKeyCredentialCreationOptionsBase(
            $this->publicKeyCredentialRpEntity,
            $this->getUserEntity($user),
            $this->getChallenge(),
            $this->createCredentialParameters(),
            $this->authenticatorSelectionCriteria,
            $this->attestationConveyance,
            $this->getExcludedCredentials($user),
            $this->timeout
        );

        return tap(PublicKeyCredentialCreationOptions::create($publicKey), function (PublicKeyCredentialCreationOptions $result) use ($user): void {
            $this->cache->put($this->cacheKey($user), (string) $result, $this->timeoutCache);
        });
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
            ->map(fn (int $algorithm): PublicKeyCredentialParameters => PublicKeyCredentialParameters::createPk($algorithm))
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
