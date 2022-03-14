<?php

namespace LaravelWebauthn\Contracts;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Webauthn\PublicKeyCredentialRequestOptions;

interface LoginViewResponse extends Responsable
{
    /**
     * Set public key request data.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Webauthn\PublicKeyCredentialRequestOptions  $publicKey
     * @return self
     */
    public function setPublicKey(Request $request, PublicKeyCredentialRequestOptions $publicKey): self;
}
