<?php

namespace LaravelWebauthn\Actions;

use Closure;
use Illuminate\Auth\Events\Failed;
use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use LaravelWebauthn\Facades\Webauthn as WebauthnFacade;
use LaravelWebauthn\Services\LoginRateLimiter;
use LaravelWebauthn\Services\Webauthn;

class AttemptToAuthenticate
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected StatefulGuard $guard,
        protected LoginRateLimiter $limiter
    ) {
    }

    /**
     * Handle the incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if (Webauthn::$authenticateUsingCallback !== null) {
            return $this->handleUsingCustomCallback($request, $next);
        }

        if ($this->attemptValidateAssertion($request)
            || $this->attemptLogin($this->filterCredentials($request), $request->boolean('remember'))) {
            return $next($request);
        }

        $this->throwFailedAuthenticationException($request);

        return null;
    }

    /**
     * Attempt to log the user into the application.
     */
    protected function attemptLogin(array $challenge, bool $remember = false): bool
    {
        return $this->guard->attempt($challenge, $remember);
    }

    /**
     * Attempt to validate assertion for authenticated user.
     */
    protected function attemptValidateAssertion(Request $request): bool
    {
        $user = $request->user();

        if ($user === null) {
            return false;
        }

        $result = WebauthnFacade::validateAssertion($user, $this->filterCredentials($request));

        if (! $result) {
            $this->fireFailedEvent($request, $user);

            $this->throwFailedAuthenticationException($request);

            return false; // @codeCoverageIgnore
        }

        return true;
    }

    /**
     * Attempt to authenticate using a custom callback.
     */
    protected function handleUsingCustomCallback(Request $request, Closure $next): mixed
    {
        $user = Webauthn::$authenticateUsingCallback !== null
            ? call_user_func(Webauthn::$authenticateUsingCallback, $request)
            : null;

        if ($user === null) {
            $this->fireFailedEvent($request);

            $this->throwFailedAuthenticationException($request);

            return null; // @codeCoverageIgnore
        }

        $this->guard->login($user, $request->boolean('remember'));

        return $next($request);
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
    protected function fireFailedEvent(Request $request, ?User $user = null): void
    {
        event(new Failed(config('webauthn.guard'), $user, [
            Webauthn::username() => $user !== null
                ? $user->{Webauthn::username()}
                : $request->{Webauthn::username()},
        ]));
    }

    /**
     * Get array of webauthn credentials.
     */
    protected function filterCredentials(Request $request): array
    {
        return $request->only(['id', 'rawId', 'response', 'type']);
    }
}
