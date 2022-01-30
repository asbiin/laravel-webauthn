<?php

namespace LaravelWebauthn\Tests\Fake;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Contracts\Foundation\Application;
use LaravelWebauthn\Models\WebauthnKey;
use LaravelWebauthn\Services\WebauthnRepository;

class FakeWebauthn extends WebauthnRepository
{
    /**
     * Laravel application.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * Create a new instance of Webauthn.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    protected $authenticate = true;

    public static function redirects(string $redirect, $default = null)
    {
        return config('webauthn.redirects.'.$redirect) ?? $default ?? config('webauthn.home');
    }

    public function setAuthenticate(bool $authenticate)
    {
        $this->authenticate = $authenticate;
    }

    public function login()
    {
        $this->app['session']->put([$this->sessionName() => true]);
    }

    public function logout()
    {
        $this->app['session']->forget($this->sessionName());
    }

    private function sessionName(): string
    {
        return $this->app['config']->get('webauthn.sessionName');
    }

    public function check(): bool
    {
        return (bool) $this->app['session']->get($this->sessionName(), false);
    }

    public function enabled(User $user): bool
    {
        return $this->app['config']->get('webauthn.enable') &&
            WebauthnKey::where('user_id', $user->getAuthIdentifier())->count() > 0;
    }

    public function canRegister(User $user): bool
    {
        return (bool) ! $this->enabled($user) || $this->check();
    }

    public function hasKey(User $user): bool
    {
        return WebauthnKey::where('user_id', $user->getAuthIdentifier())->count() > 0;
    }
}
