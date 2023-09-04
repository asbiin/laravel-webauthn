<?php

namespace LaravelWebauthn\Actions;

use Closure;
use Illuminate\Http\Request;
use LaravelWebauthn\Services\LoginRateLimiter;

class PrepareAuthenticatedSession
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
        $request->session()->regenerate();

        $this->limiter->clear($request);

        return $next($request);
    }
}
