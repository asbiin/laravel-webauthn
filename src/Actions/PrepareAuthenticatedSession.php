<?php

namespace LaravelWebauthn\Actions;

use LaravelWebauthn\Services\LoginRateLimiter;

class PrepareAuthenticatedSession
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
        $request->session()->regenerate();

        $this->limiter->clear($request);

        return $next($request);
    }
}
