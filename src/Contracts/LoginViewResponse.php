<?php

namespace LaravelWebauthn\Contracts;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use LaravelWebauthn\Services\Webauthn\PublicKeyCredentialRequestOptions;

/**
 * @psalm-mutable
 */
interface LoginViewResponse extends Responsable
{
    /**
     * Set public key request data.
     *
     * @psalm-pure
     */
    public function setPublicKey(Request $request, PublicKeyCredentialRequestOptions $publicKey): self;
}
