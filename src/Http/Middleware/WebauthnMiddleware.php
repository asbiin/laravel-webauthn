<?php

namespace LaravelWebauthn\Http\Middleware;

use Closure;
use LaravelWebauthn\Facades\Webauthn;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Config\Repository as Config;

class WebauthnMiddleware
{
    /**
     * The config repository instance.
     *
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * The auth factory instance.
     *
     * @var \Illuminate\Contracts\Auth\Factory
     */
    protected $auth;

    /**
     * Create a Webauthn.
     *
     * @param \Illuminate\Contracts\Config\Repository $config
     */
    public function __construct(Config $config, AuthFactory $auth)
    {
        $this->config = $config;
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        if ($this->config->get('webauthn.enable', true) &&
            ! Webauthn::check()) {
            abort_if($this->auth->guard($guard)->guest(), 401, trans('webauthn::errors.user_unauthenticated'));

            if (Webauthn::enabled($request->user($guard))) {
                return Redirect::guest(route('webauthn.login'));
            }
        }

        return $next($request);
    }
}
