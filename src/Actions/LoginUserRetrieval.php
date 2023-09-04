<?php

namespace LaravelWebauthn\Actions;

use Illuminate\Auth\Events\Failed;
use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use LaravelWebauthn\Services\LoginRateLimiter;
use LaravelWebauthn\Services\Webauthn;

class LoginUserRetrieval
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected LoginRateLimiter $limiter
    ) {
    }

    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): ?User
    {
        $user = $request->user() ?? $this->getUserFromCredentials($request->only([Webauthn::username(), 'password']));

        if ($user === null) {
            $this->fireFailedEvent($request);

            $this->throwFailedAuthenticationException($request);

            return null; // @codeCoverageIgnore
        }

        return $user;
    }

    /**
     * Return the user that should authenticate via WebAuthn.
     */
    protected function getUserFromCredentials(?array $credentials): ?User
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
     */
    protected function userProvider(): ?UserProvider
    {
        return Auth::createUserProvider('users');
    }

    /**
     * Throw a failed authentication validation exception.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function throwFailedAuthenticationException(Request $request): void
    {
        $this->limiter->increment($request);

        throw ValidationException::withMessages([
            Webauthn::username() => [trans('webauthn::errors.login_failed')],
        ]);
    }

    /**
     * Fire the failed authentication attempt event with the given arguments.
     */
    protected function fireFailedEvent(Request $request): void
    {
        event(new Failed(config('webauthn.guard'), null, [
            Webauthn::username() => $request->{Webauthn::username()},
        ]));
    }
}
