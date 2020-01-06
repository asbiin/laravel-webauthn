<?php

namespace LaravelWebauthn\Tests\Fake;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Contracts\Foundation\Application;
use LaravelWebauthn\Models\WebauthnKey;
use LaravelWebauthn\Services\Webauthn\PublicKeyCredentialCreationOptionsFactory;
use LaravelWebauthn\Services\Webauthn\PublicKeyCredentialRequestOptionsFactory;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRequestOptions;

class FakeWebauthn
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
     * @param \Illuminate\Contracts\Foundation\Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    protected $authenticate = true;

    public function getRegisterData(User $user): PublicKeyCredentialCreationOptions
    {
        $publicKey = $this->app->make(PublicKeyCredentialCreationOptionsFactory::class)
            ->create($user);

        return $publicKey;
    }

    public function doRegister(User $user, PublicKeyCredentialCreationOptions $publicKey, string $data, string $keyName): WebauthnKey
    {
        $webauthnKey = factory(WebauthnKey::class)->create([
            'user_id' => $user->getAuthIdentifier(),
            'name' => $keyName,
        ]);

        $this->forceAuthenticate();

        return $webauthnKey;
    }

    public function getAuthenticateData(User $user): PublicKeyCredentialRequestOptions
    {
        return $this->app->make(PublicKeyCredentialRequestOptionsFactory::class)
            ->create($user);
    }

    public function doAuthenticate(User $user, PublicKeyCredentialRequestOptions $publicKey, string $data): bool
    {
        if ($this->authenticate) {
            $this->forceAuthenticate();
        }

        return $this->authenticate;
    }

    public function setAuthenticate(bool $authenticate)
    {
        $this->authenticate = $authenticate;
    }

    public function forceAuthenticate()
    {
        $this->app['session']->put([$this->app['config']->get('webauthn.sessionName') => true]);
    }

    public function check(): bool
    {
        return (bool) $this->app['session']->get($this->app['config']->get('webauthn.sessionName'), false);
    }

    public function enabled(User $user): bool
    {
        return $this->app['config']->get('webauthn.enable') &&
            WebauthnKey::where('user_id', $user->getAuthIdentifier())->count() > 0;
    }
}
