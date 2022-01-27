<?php

namespace LaravelWebauthn\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use LaravelWebauthn\Actions\LoginAttempt;
use LaravelWebauthn\Actions\LoginPrepare;
use LaravelWebauthn\Contracts\LoginSuccessResponse;
use LaravelWebauthn\Contracts\LoginViewResponse;
use LaravelWebauthn\Http\Requests\LoginRequest;
use LaravelWebauthn\Services\Webauthn as WebauthnService;

class AuthenticateController extends Controller
{
    /**
     * Show the login Webauthn request after a login authentication.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return LoginViewResponse
     */
    public function login(Request $request)
    {
        $publicKey = app(LoginPrepare::class)($request->user());

        $request->session()->put(WebauthnService::SESSION_PUBLICKEY_REQUEST, $publicKey);

        return app(LoginViewResponse::class);
    }

    /**
     * Authenticate a webauthn request.
     *
     * @param  LoginRequest  $request
     * @return LoginSuccessResponse
     */
    public function auth(LoginRequest $request)
    {
        $publicKey = $request->session()->pull(WebauthnService::SESSION_PUBLICKEY_REQUEST);

        if (! $publicKey instanceof \Webauthn\PublicKeyCredentialRequestOptions) {
            abort(404);
        }

        app(LoginAttempt::class)(
            $request->user(),
            $publicKey,
            $request->input('data')
        );

        return app(LoginSuccessResponse::class);
    }
}
