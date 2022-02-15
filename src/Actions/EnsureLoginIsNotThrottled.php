<?php

namespace LaravelWebauthn\Actions;

use Illuminate\Auth\Events\Lockout;
use LaravelWebauthn\Services\LoginRateLimiter;
use LaravelWebauthn\Contracts\LockoutResponse;

class EnsureLoginIsNotThrottled
{
    /**
     * The login rate limiter instance.
     *
     * @var \LaravelWebauthn\Services\LoginRateLimiter
     */
    protected LoginRateLimiter $limiter;

    /**
     * Create a new class instance.
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
     * @param  callable  $next
     * @return mixed
     */
    public function handle($request, $next)
    {
        if (! $this->limiter->tooManyAttempts($request)) {
            return $next($request);
        }

        event(new Lockout($request));

        return app(LockoutResponse::class);
    }
}
