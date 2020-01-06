<?php

namespace LaravelWebauthn\Services\Webauthn;

use CBOR\Decoder;
use CBOR\OtherObject\OtherObjectManager;
use CBOR\Tag\TagObjectManager;
use Cose\Algorithm\Manager;
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
     * Create a CBOR Decoder object.
     *
     * @return Decoder
     */
    protected function createCBORDecoder(): Decoder
    {
        $otherObjectManager = new OtherObjectManager();
        $tagObjectManager = new TagObjectManager();

        return new Decoder($tagObjectManager, $otherObjectManager);
    }

    /**
     * Attestation Statement Support Manager.
     *
     * @param Decoder $decoder
     * @param Manager $coseAlgorithmManager
     * @return AttestationStatementSupportManager
     */
    protected function getAttestationStatementSupportManager(Decoder $decoder, Manager $coseAlgorithmManager): AttestationStatementSupportManager
    {
        $attestationStatementSupportManager = new AttestationStatementSupportManager();

        // https://www.w3.org/TR/webauthn/#none-attestation
        $attestationStatementSupportManager->add(new NoneAttestationStatementSupport());

        // https://www.w3.org/TR/webauthn/#fido-u2f-attestation
        $attestationStatementSupportManager->add(new FidoU2FAttestationStatementSupport($decoder));

        // https://www.w3.org/TR/webauthn/#android-safetynet-attestation
        if ($this->config->get('webauthn.google_safetynet_api_key') != null) {
            try {
                $client = \Http\Discovery\Psr18ClientDiscovery::find();
                $attestationStatementSupportManager->add(new AndroidSafetyNetAttestationStatementSupport($client, $this->config->get('webauthn.google_safetynet_api_key')));
            } catch (\Http\Discovery\Exception\NotFoundException $e) {
                // ignore
            }
        }

        // https://www.w3.org/TR/webauthn/#android-key-attestation
        $attestationStatementSupportManager->add(new AndroidKeyAttestationStatementSupport($decoder));

        // https://www.w3.org/TR/webauthn/#tpm-attestation
        $attestationStatementSupportManager->add(new TPMAttestationStatementSupport());

        // https://www.w3.org/TR/webauthn/#packed-attestation
        $attestationStatementSupportManager->add(new PackedAttestationStatementSupport($decoder, $coseAlgorithmManager));

        return $attestationStatementSupportManager;
    }

    /**
     * Get the Public Key Credential Loader.
     *
     * @param AttestationStatementSupportManager $attestationStatementSupportManager
     * @param Decoder $decoder
     * @return PublicKeyCredentialLoader
     */
    protected function getPublicKeyCredentialLoader(AttestationStatementSupportManager $attestationStatementSupportManager, Decoder $decoder): PublicKeyCredentialLoader
    {
        // Attestation Object Loader
        $attestationObjectLoader = new AttestationObjectLoader($attestationStatementSupportManager, $decoder);

        // Public Key Credential Loader
        return new PublicKeyCredentialLoader($attestationObjectLoader, $decoder);
    }
}
