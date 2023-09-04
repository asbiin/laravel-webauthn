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
            ? $this->jsonResponse($request)
            : Redirect::intended(Webauthn::redirects('register'));
    }

    /**
     * Create an HTTP response that represents the object.
     */
    protected function jsonResponse(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $callback = $request->session()->pull('url.intended', Webauthn::redirects('register'));

        return Response::json([
            'result' => $this->webauthnKey,
            'callback' => $callback,
        ], 201);
    }

    /**
     * Set the new Webauthn key.
     */
    public function setWebauthnKey(Request $request, Model $webauthnKey): self
    {
        $this->webauthnKey = $webauthnKey;

        return $this;
    }
}
