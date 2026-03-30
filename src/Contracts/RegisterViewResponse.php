<?php

namespace LaravelWebauthn\Contracts;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use LaravelWebauthn\Services\Webauthn\PublicKeyCredentialCreationOptions;

/**
 * @psalm-mutable
 */
interface RegisterViewResponse extends Responsable
{
    /**
     * Set public key request data.
     *
     * @psalm-impure
     */
    public function setPublicKey(Request $request, PublicKeyCredentialCreationOptions $publicKey): self;
}
