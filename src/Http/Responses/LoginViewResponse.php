<?php

namespace LaravelWebauthn\Http\Responses;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use LaravelWebauthn\Contracts\LoginViewResponse as LoginViewResponseContract;
use LaravelWebauthn\Services\Webauthn\PublicKeyCredentialRequestOptions;

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
    #[\Override]
    public function toResponse($request)
    {
        $view = config('webauthn.views.authenticate', '');

        $data = ['publicKey' => $this->publicKey];

        return $request->wantsJson()
            ? Response::json($data)
            : Response::view($view, $data);
    }

    /**
     * Set public key request data.
     */
    #[\Override]
    public function setPublicKey(Request $request, PublicKeyCredentialRequestOptions $publicKey): self
    {
        $this->publicKey = $publicKey;

        return $this;
    }
}
