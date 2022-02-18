<?php

namespace LaravelWebauthn\Http\Responses;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Response;
use LaravelWebauthn\Contracts\RegisterSuccessResponse as RegisterSuccessResponseContract;
use LaravelWebauthn\Facades\Webauthn;

class RegisterSuccessResponse implements RegisterSuccessResponseContract
{
    /**
     * The new Webauthn key.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected Model $webauthnKey;

    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        return $request->wantsJson()
            ? Response::json($this->webauthnKey->jsonSerialize(), 201)
            : Redirect::intended(Webauthn::redirects('register'));
    }

    /**
     * Set the new Webauthn key.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Database\Eloquent\Model  $webauthnKey
     * @return self
     */
    public function setWebauthnKey(Request $request, Model $webauthnKey): self
    {
        $this->webauthnKey = $webauthnKey;

        return $this;
    }
}
