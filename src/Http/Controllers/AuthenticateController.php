<?php

namespace LaravelWebauthn\Http\Controllers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use LaravelWebauthn\Actions\LoginAttempt;
use LaravelWebauthn\Actions\LoginPrepare;
use LaravelWebauthn\Contracts\LoginSuccessResponse;
use LaravelWebauthn\Contracts\LoginViewResponse;
use LaravelWebauthn\Http\Requests\WebauthnLoginRequest;
use LaravelWebauthn\Services\Webauthn as WebauthnService;

class AuthenticateController extends Controller
{
    /**
     * The Illuminate application instance.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * Create a new controller.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Show the login Webauthn request after a login authentication.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return LoginViewResponse
     */
    public function login(Request $request)
    {
        $publicKey = $this->app[LoginPrepare::class]($request->user());

        $request->session()->put(WebauthnService::SESSION_PUBLICKEY_REQUEST, $publicKey);

        return $this->app[LoginViewResponse::class];
    }

    /**
     * Authenticate a webauthn request.
     *
     * @param  WebauthnLoginRequest  $request
     * @return LoginSuccessResponse
     */
    public function auth(WebauthnLoginRequest $request)
    {
        $publicKey = $request->session()->pull(WebauthnService::SESSION_PUBLICKEY_REQUEST);

        if (! $publicKey instanceof \Webauthn\PublicKeyCredentialRequestOptions) {
            Log::debug('Webauthn wrong publickKey type');
            abort(404);
        }

        $this->app[LoginAttempt::class](
            $request->user(),
            $publicKey,
            $request->input('data')
        );

        return $this->app[LoginSuccessResponse::class];
    }
}
