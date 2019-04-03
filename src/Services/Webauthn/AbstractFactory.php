<?php

namespace LaravelWebauthn\Services\Webauthn;

use CBOR\Decoder;
use Cose\Algorithm\Manager;
use CBOR\Tag\TagObjectManager;
use Http\Adapter\Guzzle6\Client;
use Webauthn\PublicKeyCredentialLoader;
use CBOR\OtherObject\OtherObjectManager;
use Webauthn\PublicKeyCredentialSourceRepository;
use Illuminate\Contracts\Config\Repository as Config;
use Webauthn\AttestationStatement\AttestationObjectLoader;
use Webauthn\AttestationStatement\TPMAttestationStatementSupport;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AttestationStatement\PackedAttestationStatementSupport;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\FidoU2FAttestationStatementSupport;
use Webauthn\AttestationStatement\AndroidKeyAttestationStatementSupport;
use Webauthn\AttestationStatement\AndroidSafetyNetAttestationStatementSupport;

abstract class AbstractFactory
{
    /**
     * The config repository instance.
     *
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * Public Key Credential Source Repository.
     * @var CredentialRepository
     */
    protected $repository;

    public function __construct(Config $config, CredentialRepository $repository)
    {
        $this->config = $config;
        // Credential Repository
        $this->repository = $repository;
    }
}
