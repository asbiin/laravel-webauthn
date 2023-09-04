<?php

namespace LaravelWebauthn\Http\Responses;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use LaravelWebauthn\Contracts\LoginViewResponse as LoginViewResponseContract;
use Webauthn\PublicKeyCredentialRequestOptions;

class LoginViewResponse implements LoginViewResponseContract
{
    /**
     * The public key options.
     */
    protected PublicKeyCredentialRequestOptions $publicKey;

    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        $view = config('webauthn.views.authenticate', '');

        return $request->wantsJson()
            ? Response::json(['publicKey' => $this->publicKey])
            : Response::view($view, ['publicKey' => $this->publicKey]);
    }

    /**
     * Set public key request data.
     */
    public function setPublicKey(Request $request, PublicKeyCredentialRequestOptions $publicKey): self
    {
        $this->publicKey = $publicKey;

        return $this;
    }
}
