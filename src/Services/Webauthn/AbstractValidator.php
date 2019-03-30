<?php

namespace LaravelWebauthn\Services\Webauthn;

use CBOR\Decoder;
use Cose\Algorithm\Manager;
use CBOR\Tag\TagObjectManager;
use Webauthn\PublicKeyCredentialLoader;
use CBOR\OtherObject\OtherObjectManager;
use Illuminate\Contracts\Config\Repository as Config;
use Webauthn\AttestationStatement\AttestationObjectLoader;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AttestationStatement\PackedAttestationStatementSupport;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\FidoU2FAttestationStatementSupport;

abstract class AbstractValidator
{
    /**
     * The config repository instance.
     *
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Create a CBOR Decoder object.
     *
     * @return Decoder
     */
    protected function createCBORDecoder() : Decoder
    {
        $otherObjectManager = new OtherObjectManager();
        $tagObjectManager = new TagObjectManager();

        return new Decoder($tagObjectManager, $otherObjectManager);
    }

    /**
     * Attestation Statement Support Manager.
     *
     * @param Decoder $decoder
     * @return AttestationStatementSupportManager
     */
    protected function getAttestationStatementSupportManager(Decoder $decoder) : AttestationStatementSupportManager
    {
        $coseAlgorithmManager = new Manager();
        $coseAlgorithmManager->add(new \Cose\Algorithm\Signature\ECDSA\ES256());
        $coseAlgorithmManager->add(new \Cose\Algorithm\Signature\RSA\RS256());

        $attestationStatementSupportManager = new AttestationStatementSupportManager();
        $attestationStatementSupportManager->add(new NoneAttestationStatementSupport());
        $attestationStatementSupportManager->add(new FidoU2FAttestationStatementSupport($decoder));
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
    protected function getPublicKeyCredentialLoader(AttestationStatementSupportManager $attestationStatementSupportManager, Decoder $decoder) : PublicKeyCredentialLoader
    {
        // Attestation Object Loader
        $attestationObjectLoader = new AttestationObjectLoader($attestationStatementSupportManager, $decoder);

        // Public Key Credential Loader
        return new PublicKeyCredentialLoader($attestationObjectLoader, $decoder);
    }
}
