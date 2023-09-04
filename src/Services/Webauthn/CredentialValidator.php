<?php

namespace LaravelWebauthn\Services\Webauthn;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Http\Request;

abstract class CredentialValidator
{
    /**
     * PublicKey Request session name.
     */
    public const CACHE_PUBLICKEY_REQUEST = 'webauthn.publicKeyRequest';

    public function __construct(
        protected Request $request,
        protected Cache $cache
    ) {
    }

    /**
     * Returns the cache key to remember the challenge for the user.
     */
    protected function cacheKey(User $user): string
    {
        return implode(
            '|',
            [
                self::CACHE_PUBLICKEY_REQUEST,
                get_class($user).':'.$user->getAuthIdentifier(),
                hash('sha512', $this->request->getHost().'|'.$this->request->ip()),
            ]
        );
    }
}
