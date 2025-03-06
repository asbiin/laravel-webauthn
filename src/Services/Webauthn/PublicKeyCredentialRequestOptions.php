<?php

namespace LaravelWebauthn\Services\Webauthn;

use LaravelWebauthn\Services\JsonWrapper;
use Webauthn\PublicKeyCredentialRequestOptions as PublicKeyCredentialRequestOptionsBase;

/**
 * @extends JsonWrapper<PublicKeyCredentialRequestOptionsBase>
 */
final class PublicKeyCredentialRequestOptions extends JsonWrapper
{
    /**
     * Create a PublicKeyCredentialRequestOptions
     */
    public static function create(PublicKeyCredentialRequestOptionsBase $data): self
    {
        return app(self::class, ['data' => $data]);
    }
}
