<?php

namespace LaravelWebauthn\Services\Webauthn;

use Illuminate\Contracts\Config\Repository as Config;

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
