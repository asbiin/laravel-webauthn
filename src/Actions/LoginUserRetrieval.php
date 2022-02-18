<?php

namespace LaravelWebauthn\Actions;

use Illuminate\Auth\Events\Failed;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use LaravelWebauthn\Services\LoginRateLimiter;
use LaravelWebauthn\Services\Webauthn;

class LoginUserRetrieval
{
    /**
     * The login rate limiter instance.
     *
     * @var \LaravelWebauthn\Services\LoginRateLimiter
     */
    protected LoginRateLimiter $limiter;

    /**
     * Create a new controller instance.
     *
     * @param  \LaravelWebauthn\Services\LoginRateLimiter  $limiter
     * @return void
     */
    public function __construct(LoginRateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return Authenticatable|null
     */
    public function __invoke(Request $request): ?Authenticatable
    {
        $user = $request->user() ?? $this->getUserFromCredentials($request->only([Webauthn::username(), 'password']));

        if ($user === null) {
            $this->fireFailedEvent($request);

            $this->throwFailedAuthenticationException($request);

            return null;
        }

        return $user;
    }

    /**
     * Return the user that should authenticate via WebAuthn.
     *
     * @param  array|null  $credentials
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    protected function getUserFromCredentials(?array $credentials): ?Authenticatable
    {
        // We will try to ask the User Provider for any user for the given credentials.
        // If there is one, we will then return an array of credentials ID that the
        // authenticator may use to sign the subsequent challenge by the server.

        $userProvider = $this->userProvider();

        return $userProvider !== null && $credentials !== null
            ? $userProvider->retrieveByCredentials($credentials)
            : null;
    }

    /**
     * Get the User Provider for WebAuthn Authenticatable users.
     *
     * @return \Illuminate\Contracts\Auth\UserProvider|null
     */
    protected function userProvider(): ?UserProvider
    {
        return Auth::createUserProvider('users');
    }

    /**
     * Throw a failed authentication validation exception.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function throwFailedAuthenticationException(Request $request)
    {
        $this->limiter->increment($request);

        throw ValidationException::withMessages([
            Webauthn::username() => [trans('webauthn::errors.login_failed')],
        ]);
    }

    /**
     * Fire the failed authentication attempt event with the given arguments.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function fireFailedEvent(Request $request)
    {
        event(new Failed(config('webauthn.guard'), null, [
            Webauthn::username() => $request->{Webauthn::username()},
        ]));
    }
}
