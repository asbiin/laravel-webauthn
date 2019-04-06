<?php

namespace LaravelWebauthn\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\URL;
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
    protected $factory;

    /**
     * Create a Webauthn.
     *
     * @param \Illuminate\Contracts\Config\Repository $config
     */
    public function __construct(Config $config, AuthFactory $factory)
    {
        $this->config = $config;
        $this->factory = $factory;
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
        if ($this->config->get('webauthn.enable') &&
            ! Webauthn::check()) {
            abort_if($this->factory->guard($guard)->guest(), 401, 'You need to log in before doing a Webauthn authentication');

            if (Webauthn::enabled($request->user())) {
                return Redirect::guest(route('webauthn.login').'?callback='.urlencode(URL::current()));
            }
        }

        return $next($request);
    }
}
