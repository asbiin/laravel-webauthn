<?php

namespace LaravelWebauthn\Services\Webauthn;

use Cose\Algorithm\Manager;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Log;
use Webauthn\AttestationStatement\AndroidKeyAttestationStatementSupport;
use Webauthn\AttestationStatement\AndroidSafetyNetAttestationStatementSupport;
use Webauthn\AttestationStatement\AttestationObjectLoader;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\FidoU2FAttestationStatementSupport;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AttestationStatement\PackedAttestationStatementSupport;
use Webauthn\AttestationStatement\TPMAttestationStatementSupport;
use Webauthn\PublicKeyCredentialLoader;

abstract class AbstractValidatorFactory extends AbstractFactory
{
    /**
     * Attestation Statement Support Manager.
     *
     * @param  Manager  $coseAlgorithmManager
     * @return AttestationStatementSupportManager
     */
    protected function getAttestationStatementSupportManager(Manager $coseAlgorithmManager): AttestationStatementSupportManager
    {
        $attestationStatementSupportManager = new AttestationStatementSupportManager();

        // https://www.w3.org/TR/webauthn/#sctn-none-attestation
        $attestationStatementSupportManager->add(new NoneAttestationStatementSupport());

        // https://www.w3.org/TR/webauthn/#sctn-fido-u2f-attestation
        $attestationStatementSupportManager->add(new FidoU2FAttestationStatementSupport());

        // https://www.w3.org/TR/webauthn/#sctn-android-safetynet-attestation
        if (($google_safetynet_api_key = $this->config->get('webauthn.google_safetynet_api_key')) !== null) {
            $psr = $this->getPsrInterfaces();
            $attestationStatementSupportManager->add(
                (new AndroidSafetyNetAttestationStatementSupport())
                    ->enableApiVerification($psr['client'], $google_safetynet_api_key, $psr['requestFactory'])
            );
        }

        // https://www.w3.org/TR/webauthn/#sctn-android-key-attestation
        $attestationStatementSupportManager->add(new AndroidKeyAttestationStatementSupport());

        // https://www.w3.org/TR/webauthn/#sctn-tpm-attestation
        $attestationStatementSupportManager->add(new TPMAttestationStatementSupport());

        // https://www.w3.org/TR/webauthn/#sctn-packed-attestation
        $attestationStatementSupportManager->add(new PackedAttestationStatementSupport($coseAlgorithmManager));

        return $attestationStatementSupportManager;
    }

    /**
     * Get the Public Key Credential Loader.
     *
     * @param  AttestationStatementSupportManager  $attestationStatementSupportManager
     * @return PublicKeyCredentialLoader
     */
    protected function getPublicKeyCredentialLoader(AttestationStatementSupportManager $attestationStatementSupportManager): PublicKeyCredentialLoader
    {
        // Attestation Object Loader
        $attestationObjectLoader = new AttestationObjectLoader($attestationStatementSupportManager);

        // Public Key Credential Loader
        return new PublicKeyCredentialLoader($attestationObjectLoader);
    }

    /**
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    private function getPsrInterfaces(): array
    {
        if (class_exists(\Http\Discovery\Psr18ClientDiscovery::class) && class_exists(\Http\Discovery\Psr17FactoryDiscovery::class)) {
            $result = [];
            try {
                $result['client'] = \Http\Discovery\Psr18ClientDiscovery::find();
            } catch (\Http\Discovery\Exception\NotFoundException $e) {
                Log::error('Could not find PSR-18 Client Factory.', ['exception' => $e]);
                throw new BindingResolutionException('Unable to resolve PSR-18 Client Factory. Please install a psr/http-client-implementation implementation like \'guzzlehttp/guzzle\'.');
            }
            try {
                $result['requestFactory'] = \Http\Discovery\Psr17FactoryDiscovery::findRequestFactory();
            } catch (\Http\Discovery\Exception\NotFoundException $e) {
                Log::error('Could not find PSR-17 Request Factory.', ['exception' => $e]);
                throw new BindingResolutionException('Unable to resolve PSR-17 Request Factory. Please install psr/http-factory-implementation implementation like \'guzzlehttp/psr7\'.');
            }
            return $result;
        }

        throw new BindingResolutionException('Unable to resolve PSR request. Please install php-http/discovery and implementations for psr/http-factory-implementation and psr/http-client-implementation.');
    }
}
