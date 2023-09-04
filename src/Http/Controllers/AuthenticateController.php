<?php

namespace LaravelWebauthn\Http\Controllers;

use Illuminate\Pipeline\Pipeline;
use Illuminate\Routing\Controller;
use LaravelWebauthn\Actions\AttemptToAuthenticate;
use LaravelWebauthn\Actions\EnsureLoginIsNotThrottled;
use LaravelWebauthn\Actions\LoginUserRetrieval;
use LaravelWebauthn\Actions\PrepareAssertionData;
use LaravelWebauthn\Actions\PrepareAuthenticatedSession;
use LaravelWebauthn\Contracts\LoginSuccessResponse;
use LaravelWebauthn\Contracts\LoginViewResponse;
use LaravelWebauthn\Http\Requests\WebauthnLoginAttemptRequest;
use LaravelWebauthn\Http\Requests\WebauthnLoginRequest;
use LaravelWebauthn\Services\Webauthn;

class AuthenticateController extends Controller
{
    /**
     * Show the login Webauthn request after a login authentication.
     */
    public function create(WebauthnLoginAttemptRequest $request): LoginViewResponse
    {
        $user = $this->createPipeline($request)->then(function ($request) {
            return app(LoginUserRetrieval::class)($request);
        });

        $publicKey = app(PrepareAssertionData::class)($user);

        return app(LoginViewResponse::class)
            ->setPublicKey($request, $publicKey);
    }

    /**
     * Get the authentication pipeline instance.
     */
    protected function createPipeline(WebauthnLoginAttemptRequest $request): Pipeline
    {
        return (new Pipeline(app()))
            ->send($request)
            ->through(array_filter([
                config('webauthn.limiters.login') !== null ? null : EnsureLoginIsNotThrottled::class,
            ]));
    }

    /**
     * Authenticate a webauthn request.
     */
    public function store(WebauthnLoginRequest $request): LoginSuccessResponse
    {
        return $this->loginPipeline($request)->then(function ($request) {
            Webauthn::login($request->user());

            return app(LoginSuccessResponse::class);
        });
    }

    /**
     * Get the authentication pipeline instance.
     */
    protected function loginPipeline(WebauthnLoginRequest $request): Pipeline
    {
        if (Webauthn::$authenticateThroughCallback !== null) {
            return (new Pipeline(app()))->send($request)->through(array_filter(
                call_user_func(Webauthn::$authenticateThroughCallback, $request)
            ));
        }

        if (is_array($pipelines = config('webauthn.pipelines.login'))) {
            return (new Pipeline(app()))->send($request)->through(array_filter(
                $pipelines
            ));
        }

        return (new Pipeline(app()))->send($request)->through(array_filter([
            config('webauthn.limiters.login') !== null ? null : EnsureLoginIsNotThrottled::class,
            AttemptToAuthenticate::class,
            PrepareAuthenticatedSession::class,
        ]));
    }
}
