<?php

namespace LaravelWebauthn\Services\Webauthn;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Request;
use Webauthn\PublicKeyCredentialSourceRepository;

abstract class OptionsFactory extends CredentialValidator
{
    /**
     * Public Key Credential Source Repository.
     *
     * @var CredentialRepository
     */
    protected CredentialRepository $repository;

    /**
     * Number random bytes.
     *
     * @var int
     */
    protected int $challengeLength;

    /**
     * Timeout in seconds.
     *
     * @var int
     */
    protected int $timeout;

    public function __construct(Request $request, Cache $cache, Config $config, PublicKeyCredentialSourceRepository $repository)
    {
        parent::__construct($request, $cache);

        if ($repository instanceof CredentialRepository) {
            $this->repository = $repository;
        }

        $this->challengeLength = (int) $config->get('webauthn.challenge_length', 32);
        $this->timeout = (int) $config->get('webauthn.timeout', 60000);
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
