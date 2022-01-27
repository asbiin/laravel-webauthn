<?php

namespace LaravelWebauthn\Services\Webauthn;

use Cose\Algorithm\Manager;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\RequestFactoryInterface;
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
            try {
                $client = \Http\Discovery\Psr18ClientDiscovery::find();
                $requestFactory = \Http\Discovery\Psr17FactoryDiscovery::findRequestFactory();
                $attestationStatementSupportManager->add(
                    (new AndroidSafetyNetAttestationStatementSupport())
                        ->enableApiVerification($client, $google_safetynet_api_key, $requestFactory)
                );
            } catch (\Http\Discovery\Exception\NotFoundException $e) {
                Log::error("Either Psr18Client or Psr17Factory not found.", ['exception' => $e]);
                // ignore
            }
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
}
