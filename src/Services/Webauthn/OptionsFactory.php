<?php

namespace LaravelWebauthn\Services\Webauthn;

use Illuminate\Contracts\Config\Repository as Config;
use Webauthn\PublicKeyCredentialSourceRepository;

abstract class OptionsFactory
{
    /**
     * Public Key Credential Source Repository.
     *
     * @var CredentialRepository
     */
    protected $repository;

    /**
     * Number random bytes.
     *
     * @var int
     */
    protected $challengeLength;

    /**
     * Timeout in seconds.
     *
     * @var int
     */
    protected $timeout;

    public function __construct(Config $config, PublicKeyCredentialSourceRepository $repository)
    {
        if ($repository instanceof CredentialRepository) {
            $this->repository = $repository;
        }

        $this->challengeLength = (int) $config->get('webauthn.challenge_length', 32);
        $this->timeout = (int) $config->get('webauthn.timeout', 30);
    }

    /**
     * Get a challenge sequence.
     *
     * @return string
     *
     * @psalm-suppress ArgumentTypeCoercion
     */
    protected function getChallenge(): string
    {
        return \random_bytes($this->challengeLength);
    }
}
