<?php

namespace LaravelWebauthn\Services\Webauthn;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Http\Request;

abstract class CredentialValidator
{
    /**
     * PublicKey Request session name.
     *
     * @var string
     */
    public const CACHE_PUBLICKEY_REQUEST = 'webauthn.publicKeyRequest';

    /**
     * HTTP Request.
     *
     * @var \Illuminate\Http\Request
     */
    protected Request $request;

    /**
     * Cache repository.
     *
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected Cache $cache;

    public function __construct(Request $request, Cache $cache)
    {
        $this->request = $request;
        $this->cache = $cache;
    }

    /**
     * Returns the cache key to remember the challenge for the user.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return string
     */
    protected function cacheKey(Authenticatable $user): string
    {
        return implode(
            '|',
            [
                self::CACHE_PUBLICKEY_REQUEST,
                get_class($user).':'.$user->getAuthIdentifier(),
                hash("sha512", $this->request->getHost().'|'.$this->request->ip()),
            ]
        );
    }
}
