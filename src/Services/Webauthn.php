<?php

namespace LaravelWebauthn\Services;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Session\Session;

class Webauthn extends WebauthnRepository
{
    /**
     * PublicKey Creation session name.
     *
     * @var string
     */
    public const SESSION_PUBLICKEY_CREATION = 'webauthn.publicKeyCreation';

    /**
     * Webauthn Created ID.
     *
     * @var string
     */
    public const SESSION_WEBAUTHNID_CREATED = 'webauthn.idCreated';

    /**
     * PublicKey Request session name.
     *
     * @var string
     */
    public const SESSION_PUBLICKEY_REQUEST = 'webauthn.publicKeyRequest';

    /**
     * PublicKey Request session name.
     *
     * @var string
     */
    public const SESSION_AUTH_RESULT = 'webauthn.publicKeyRequest';

    /**
     * Laravel application.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * Configuratoin repository.
     *
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * Session manager.
     *
     * @var \Illuminate\Contracts\Session\Session
     */
    protected $session;

    /**
     * Event dispatcher.
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected $events;

    /**
     * Create a new instance of Webauthn.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @param  \Illuminate\Contracts\Config\Repository  $config
     * @param  \Illuminate\Contracts\Session\Session  $session
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     */
    public function __construct(Application $app, Config $config, Session $session, Dispatcher $events)
    {
        $this->app = $app;
        $this->config = $config;
        $this->session = $session;
        $this->events = $events;
    }

    /**
     * Get a completion redirect path for a specific feature.
     *
     * @param  string  $redirect
     * @return string
     */
    public static function redirects(string $redirect, $default = null)
    {
        return config('webauthn.redirects.'.$redirect) ?? $default ?? config('webauthn.home');
    }

    /**
     * Force authentication in session.
     *
     * @return void
     */
    public function forceAuthenticate()
    {
        $this->session->put([$this->sessionName() => true]);
    }

    /**
     * Force remove authentication in session.
     *
     * @return void
     */
    public function forgetAuthenticate()
    {
        $this->session->forget($this->sessionName());
    }

    /**
     * Check authentication of the user in session.
     *
     * @return bool
     */
    public function check(): bool
    {
        return (bool) $this->session->get($this->sessionName(), false);
    }

    /**
     * Get webauthn session store name.
     *
     * @return string
     */
    private function sessionName(): string
    {
        return $this->config->get('webauthn.sessionName');
    }

    /**
     * Test if the user has one or more webauthn key.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return bool
     */
    public function enabled(User $user): bool
    {
        return $this->webauthnEnabled() && $this->hasKey($user);
    }

    /**
     * Test if the user can register a new key.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return bool
     */
    public function canRegister(User $user): bool
    {
        return $this->webauthnEnabled() && (! $this->enabled($user) || $this->check());
    }

    /**
     * Test if webauthn is enabled.
     *
     * @return bool
     */
    public function webauthnEnabled(): bool
    {
        return (bool) $this->config->get('webauthn.enable', true);
    }

    /**
     * Register a class / callback that should be used to the destroy view response.
     *
     * @param  string  $callback
     * @return void
     */
    public static function destroyViewResponseUsing(string $callback)
    {
        app()->singleton(\LaravelWebauthn\Contracts\DestroyResponse::class, $callback);
    }

    /**
     * Register a class / callback that should be used to the update view response.
     *
     * @param  string  $callback
     * @return void
     */
    public static function updateViewResponseUsing(string $callback)
    {
        app()->singleton(\LaravelWebauthn\Contracts\UpdateResponse::class, $callback);
    }

    /**
     * Register a class / callback that should be used to the destroy view response.
     *
     * @param  string  $callback
     * @return void
     */
    public static function loginSuccessResponseUsing(string $callback)
    {
        app()->singleton(\LaravelWebauthn\Contracts\LoginSuccessResponse::class, $callback);
    }

    /**
     * Register a class / callback that should be used to the destroy view response.
     *
     * @param  string  $callback
     * @return void
     */
    public static function loginViewResponseUsing(string $callback)
    {
        app()->singleton(\LaravelWebauthn\Contracts\LoginViewResponse::class, $callback);
    }

    /**
     * Register a class / callback that should be used to the destroy view response.
     *
     * @param  string  $callback
     * @return void
     */
    public static function registerSuccessResponseUsing(string $callback)
    {
        app()->singleton(\LaravelWebauthn\Contracts\RegisterSuccessResponse::class, $callback);
    }

    /**
     * Register a class / callback that should be used to the destroy view response.
     *
     * @param  string  $callback
     * @return void
     */
    public static function registerViewResponseUsing(string $callback)
    {
        app()->singleton(\LaravelWebauthn\Contracts\RegisterViewResponse::class, $callback);
    }
}
