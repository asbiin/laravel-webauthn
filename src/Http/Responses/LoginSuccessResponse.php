<?php

namespace LaravelWebauthn\Http\Responses;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Response;
use LaravelWebauthn\Contracts\LoginSuccessResponse as LoginSuccessResponseContract;
use LaravelWebauthn\Facades\Webauthn;

class LoginSuccessResponse implements LoginSuccessResponseContract
{
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
            : Redirect::intended(Webauthn::redirects('login'));
    }

    /**
     * Create an HTTP response that represents the object.
     */
    protected function jsonResponse(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $callback = $request->session()->pull('url.intended', Webauthn::redirects('login'));

        return Response::json([
            'result' => Webauthn::check(),
            'callback' => $callback,
        ]);
    }
}
