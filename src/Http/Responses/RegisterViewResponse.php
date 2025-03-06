<?php

namespace LaravelWebauthn\Http\Responses;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use LaravelWebauthn\Contracts\RegisterViewResponse as RegisterViewResponseContract;
use LaravelWebauthn\Services\Webauthn\PublicKeyCredentialCreationOptions;

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
    #[\Override]
    public function toResponse($request)
    {
        $view = config('webauthn.views.register', '');

        $data = ['publicKey' => $this->publicKey];

        return $request->wantsJson()
            ? Response::json($data)
            : Response::view($view, $data);
    }

    /**
     * Set public key request data.
     */
    #[\Override]
    public function setPublicKey(Request $request, PublicKeyCredentialCreationOptions $publicKey): self
    {
        $this->publicKey = $publicKey;

        return $this;
    }
}
