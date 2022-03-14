<?php

namespace LaravelWebauthn\Contracts;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Webauthn\PublicKeyCredentialCreationOptions;

interface RegisterViewResponse extends Responsable
{
    /**
     * Set public key request data.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Webauthn\PublicKeyCredentialCreationOptions  $publicKey
     * @return self
     */
    public function setPublicKey(Request $request, PublicKeyCredentialCreationOptions $publicKey): self;
}
