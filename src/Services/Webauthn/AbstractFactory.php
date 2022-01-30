<?php

namespace LaravelWebauthn\Services\Webauthn;

use Illuminate\Contracts\Config\Repository as Config;
use Webauthn\PublicKeyCredentialSourceRepository;

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
     *
     * @var CredentialRepository
     */
    protected $repository;

    public function __construct(Config $config, PublicKeyCredentialSourceRepository $repository)
    {
        $this->config = $config;
        if ($repository instanceof CredentialRepository) {
            $this->repository = $repository;
        }
    }
}
