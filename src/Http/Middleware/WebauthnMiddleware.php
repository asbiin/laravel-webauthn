<?php

namespace LaravelWebauthn\Http\Middleware;

use Closure;
use Illuminate\Support\Str;
use LaravelWebauthn\Facades\Webauthn;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use LaravelWebauthn\Models\WebauthnKey;
use Illuminate\Support\Facades\Redirect;
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
     * Create a Webauthn
     * 
     * @param \Illuminate\Contracts\Config\Repository $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
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
        if($this->config->get('webauthn.enable') &&
            ! Webauthn::check()) {
            abort_if(Auth::guest(), 401, 'You need to log in before doing a Webauthn authentication');

            if (WebauthnKey::where('user_id', $request->user()->getAuthIdentifier())->count() !== 0) {
                return Redirect::guest(Str::finish($this->config->get('webauthn.prefix'), '/').'auth');
            }
        }

        return $next($request);
    }
}
