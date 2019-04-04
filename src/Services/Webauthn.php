<?php

namespace LaravelWebauthn\Services;

use Illuminate\Support\Facades\Event;
use LaravelWebauthn\Models\WebauthnKey;
use Illuminate\Contracts\Session\Session;
use LaravelWebauthn\Events\WebauthnLogin;
use LaravelWebauthn\Events\WebauthnRegister;
use Webauthn\PublicKeyCredentialRequestOptions;
use Illuminate\Contracts\Foundation\Application;
use LaravelWebauthn\Events\WebauthnRegisterData;
use Webauthn\PublicKeyCredentialCreationOptions;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Auth\Authenticatable as User;
use LaravelWebauthn\Services\Webauthn\PublicKeyCredentialValidator;
use LaravelWebauthn\Services\Webauthn\PublicKeyCredentialRequestOptionsFactory;
use LaravelWebauthn\Services\Webauthn\PublicKeyCredentialCreationOptionsFactory;

class Webauthn extends WebauthnRepository
{
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
     * Create a new instance of Webauthn.
     *
     * @param \Illuminate\Contracts\Foundation\Application $app
     * @param \Illuminate\Contracts\Config\Repository $config
     * @param \Illuminate\Contracts\Session\Session $session
     */
    public function __construct(Application $app, Config $config, Session $session)
    {
        $this->app = $app;
        $this->config = $config;
        $this->session = $session;
    }

    /**
     * Get datas to register a new key.
     *
     * @param User $user
     * @return PublicKeyCredentialCreationOptions
     */
    public function getRegisterData(User $user) : PublicKeyCredentialCreationOptions
    {
        $publicKey = $this->app->make(PublicKeyCredentialCreationOptionsFactory::class)
            ->create($user);

        Event::dispatch(new WebauthnRegisterData($user, $publicKey));

        return $publicKey;
    }

    /**
     * Register a new key.
     *
     * @param User $user
     * @param PublicKeyCredentialCreationOptions $publicKey
     * @param string $data
     * @param string $keyName
     * @return WebauthnKey
     */
    public function doRegister(User $user, PublicKeyCredentialCreationOptions $publicKey, string $data, string $keyName) : WebauthnKey
    {
        $publicKeyCredentialSource = $this->app->make(PublicKeyCredentialValidator::class)
            ->validate($publicKey, $data);

        $webauthnKey = $this->create($user, $keyName, $publicKeyCredentialSource);

        $this->forceAuthenticate();

        Event::dispatch(new WebauthnRegister($webauthnKey));

        return $webauthnKey;
    }

    /**
     * Get datas to authenticate a user.
     *
     * @param User $user
     * @return PublicKeyCredentialRequestOptions
     */
    public function getAuthenticateData(User $user) : PublicKeyCredentialRequestOptions
    {
        return $this->app->make(PublicKeyCredentialRequestOptionsFactory::class)
            ->create($user);
    }

    /**
     * Authenticate a user.
     *
     * @param User $user
     * @param PublicKeyCredentialRequestOptions $publicKey
     * @param string $data
     * @return bool
     */
    public function doAuthenticate(User $user, PublicKeyCredentialRequestOptions $publicKey, string $data): bool
    {
        $result = $this->app->make(PublicKeyCredentialValidator::class)
            ->check($user, $publicKey, $data);

        if ($result) {
            $this->forceAuthenticate();

            Event::dispatch(new WebauthnLogin($user));

            return true;
        }

        return false;
    }

    /**
     * Force authentication in session.
     *
     * @return void
     */
    public function forceAuthenticate()
    {
        $this->session->put([$this->config->get('webauthn.sessionName') => true]);
    }

    /**
     * Check authentication of the user in session.
     *
     * @return bool
     */
    public function check() : bool
    {
        return (bool) $this->session->get($this->config->get('webauthn.sessionName'), false);
    }

    /**
     * Test if the user has one webauthn key set or more.
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return bool
     */
    public function enabled(User $user): bool
    {
        return $this->config->get('webauthn.enable') && $this->hasKey($user);
    }
}
