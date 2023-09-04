<?php

namespace LaravelWebauthn\Http\Responses;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use LaravelWebauthn\Contracts\RegisterViewResponse as RegisterViewResponseContract;
use Webauthn\PublicKeyCredentialCreationOptions;

class RegisterViewResponse implements RegisterViewResponseContract
{
    /**
     * The public key options.
     */
    protected PublicKeyCredentialCreationOptions $publicKey;

    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        $view = config('webauthn.views.register', '');

        return $request->wantsJson()
            ? Response::json(['publicKey' => $this->publicKey])
            : Response::view($view, ['publicKey' => $this->publicKey]);
    }

    /**
     * Set public key request data.
     */
    public function setPublicKey(Request $request, PublicKeyCredentialCreationOptions $publicKey): self
    {
        $this->publicKey = $publicKey;

        return $this;
    }
}
