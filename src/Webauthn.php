<?php

namespace LaravelWebauthn;

use Illuminate\Support\Facades\Event;
use LaravelWebauthn\Models\WebauthnKey;
use Illuminate\Contracts\Session\Session;
use LaravelWebauthn\Events\WebauthnLogin;
use Webauthn\PublicKeyCredentialRequestOptions;
use Illuminate\Contracts\Foundation\Application;
use Webauthn\PublicKeyCredentialCreationOptions;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Auth\Authenticatable as User;
use LaravelWebauthn\Services\Webauthn\PublicKeyCredentialRequestOptionsFactory;
use LaravelWebauthn\Services\Webauthn\PublicKeyCredentialCreationOptionsFactory;
use LaravelWebauthn\Services\Webauthn\PublicKeyCredentialRequestValidatorFactory;
use LaravelWebauthn\Services\Webauthn\PublicKeyCredentialCreationValidatorFactory;

class Webauthn
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
     * @param User $user
     * @return PublicKeyCredentialCreationOptions
     */
    public function getRegisterData(User $user) : PublicKeyCredentialCreationOptions
    {
        return $this->app->make(PublicKeyCredentialCreationOptionsFactory::class)
            ->create($user);
    }

    /**
     * @param User $user
     * @param PublicKeyCredentialCreationOptions $publicKey
     * @param string $data
     * @param string $keyName
     * @return WebauthnKey
     */
    public function doRegister(User $user, PublicKeyCredentialCreationOptions $publicKey, string $data, string $keyName) : WebauthnKey
    {
        list(
            $publicKeyCredentialDescriptor,
            $attestedCredentialData
         ) = $this->app->make(PublicKeyCredentialCreationValidatorFactory::class)
            ->validate($publicKey, $data);

        return WebauthnKey::create([
            'user_id' => $user->getAuthIdentifier(),
            'name' => $keyName,
            'publicKeyCredentialDescriptor' => $publicKeyCredentialDescriptor,
            'attestedCredentialData' => $attestedCredentialData,
            'credentialId' => $publicKeyCredentialDescriptor->getId(),
        ]);
    }

    /**
     * @param User $user
     * @return PublicKeyCredentialRequestOptions
     */
    public function getAuthenticateData(User $user) : PublicKeyCredentialRequestOptions
    {
        // List of registered PublicKeyCredentialDescriptor classes associated to the user
        $registeredPublicKeyCredentialDescriptors = [];
        $webAuthns = WebauthnKey::where('user_id', $user->getAuthIdentifier())->get();
        foreach ($webAuthns as $webAuthn) {
            $registeredPublicKeyCredentialDescriptors[] = $webAuthn->publicKeyCredentialDescriptor;
        }

        return $this->app->make(PublicKeyCredentialRequestOptionsFactory::class)
            ->create($registeredPublicKeyCredentialDescriptors);
    }

    /**
     * @param User $user
     * @param PublicKeyCredentialRequestOptions $publicKey
     * @param string $data
     * @return bool
     */
    public function doAuthenticate(User $user, PublicKeyCredentialRequestOptions $publicKey, string $data): bool
    {
        $result = $this->app->make(PublicKeyCredentialRequestValidatorFactory::class)
            ->check($user, $publicKey, $data);

        if ($result) {
            $this->session->put([$this->config->get('webauthn.sessionName') => true]);
        }

        return true;
    }

    /**
     * @param User $user
     */
    public function forceAuthenticate(User $user)
    {
        if (config('webauthn.enable') && WebauthnKey::where('user_id', $user->getAuthIdentifier())->count() > 0) {
            $this->session->put([$this->config->get('webauthn.sessionName') => true]);
        }
    }

    /**
     * @return bool
     */
    public function check() : bool
    {
        return (bool) $this->session->get($this->config->get('webauthn.sessionName'), false);
    }

    /**
     * Fire the login event.
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return void
     */
    public function fireLoginEvent(User $user)
    {
        Event::dispatch(new WebauthnLogin($user));
    }
}
