<?php

namespace LaravelWebauthn\Services\Webauthn;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Request;

abstract class OptionsFactory extends CredentialValidator
{
    /**
     * Number random bytes.
     */
    protected int $challengeLength;

    /**
     * Timeout in seconds.
     */
    protected int $timeout;

    public function __construct(
        Request $request,
        Cache $cache,
        Config $config,
        protected CredentialRepository $repository
    ) {
        parent::__construct($request, $cache);

        $this->challengeLength = (int) $config->get('webauthn.challenge_length', 32);
        $this->timeout = (int) $config->get('webauthn.timeout', 60000);
    }

    /**
     * Get a challenge sequence.
     *
     * @psalm-suppress ArgumentTypeCoercion
     */
    protected function getChallenge(): string
    {
        return \random_bytes($this->challengeLength);
    }
}
