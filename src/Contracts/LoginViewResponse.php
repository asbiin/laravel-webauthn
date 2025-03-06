<?php

namespace LaravelWebauthn\Contracts;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use LaravelWebauthn\Services\Webauthn\PublicKeyCredentialRequestOptions;

interface LoginViewResponse extends Responsable
{
    /**
     * Set public key request data.
     */
    public function setPublicKey(Request $request, PublicKeyCredentialRequestOptions $publicKey): self;
}
