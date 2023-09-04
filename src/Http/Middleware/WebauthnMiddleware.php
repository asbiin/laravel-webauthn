<?php

namespace LaravelWebauthn\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use LaravelWebauthn\Facades\Webauthn;

class WebauthnMiddleware
{
    /**
     * Create a Webauthn middleware.
     */
    public function __construct(
        protected AuthFactory $auth
    ) {
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ?string $guard = null): mixed
    {
        if (Webauthn::webauthnEnabled() && ! Webauthn::check()) {
            abort_if($this->auth->guard($guard)->guest(), 401, /** @var string $m */ $m = trans('webauthn::errors.user_unauthenticated'));

            if (Webauthn::enabled($request->user($guard))) {
                if ($request->hasSession() && $request->session()->has('url.intended')) {
                    return Redirect::to(route('webauthn.login'));
                } else {
                    return Redirect::guest(route('webauthn.login'));
                }
            }
        }

        return $next($request);
    }
}
