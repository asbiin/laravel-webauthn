<?php

namespace LaravelWebauthn\Actions;

use Closure;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\Request;
use LaravelWebauthn\Contracts\LockoutResponse;
use LaravelWebauthn\Services\LoginRateLimiter;

class EnsureLoginIsNotThrottled
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        protected LoginRateLimiter $limiter
    ) {
    }

    /**
     * Handle the incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if (! $this->limiter->tooManyAttempts($request)) {
            return $next($request);
        }

        event(new Lockout($request));

        return app(LockoutResponse::class);
    }
}
