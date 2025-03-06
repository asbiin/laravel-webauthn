<?php

namespace LaravelWebauthn\Services\Webauthn;

use LaravelWebauthn\Services\JsonWrapper;
use Webauthn\PublicKeyCredentialCreationOptions as PublicKeyCredentialCreationOptionsBase;

/**
 * @extends JsonWrapper<PublicKeyCredentialCreationOptionsBase>
 */
final class PublicKeyCredentialCreationOptions extends JsonWrapper
{
    /**
     * Create a PublicKeyCredentialCreationOptions
     */
    public static function create(PublicKeyCredentialCreationOptionsBase $data): self
    {
        return app(self::class, ['data' => $data]);
    }
}
