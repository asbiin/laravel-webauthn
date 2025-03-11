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
     * Timeout in milliseconds.
     */
    protected ?int $timeout = null;

    /**
     * Timeout in milliseconds.
     */
    protected ?int $timeoutCache;

    public function __construct(
        Request $request,
        Cache $cache,
        Config $config
    ) {
        parent::__construct($request, $cache);

        $this->challengeLength = (int) $config->get('webauthn.challenge_length', 32);
        $timeout = $config->get('webauthn.timeout');
        if ($timeout !== null) {
            $this->timeout = (int) $timeout;
            $this->timeoutCache = $this->timeout / 1000;
        } else {
            $this->timeoutCache = self::getDefaultTimeoutCache($config);
        }
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

    /**
     * See https://webauthn-doc.spomky-labs.com/symfony-bundle/configuration-references#timeout
     */
    private static function getDefaultTimeoutCache(Config $config): ?int
    {
        switch ($config->get('webauthn.user_verification')) {
            case 'discouraged':
                return 180;
            case 'preferred':
            case 'required':
                return 600;
            default:
                return null;
        }
    }
}
