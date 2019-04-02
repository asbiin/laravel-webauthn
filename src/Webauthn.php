<?php

namespace LaravelWebauthn;

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
        $publicKey = $this->app->make(PublicKeyCredentialCreationOptionsFactory::class)
            ->create($user, $this->getRegisteredKeys($user));

        Event::dispatch(new WebauthnRegisterData($user, $publicKey));

        return $publicKey;
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
        $publicKeyCredentialSource = $this->app->make(PublicKeyCredentialCreationValidatorFactory::class)
            ->validate($publicKey, $data);

        $webauthnKey = WebauthnKey::create([
            'user_id' => $user->id,
            'name' => $keyName,
        ]);
        $webauthnKey->setPublicKeyCredentialSource($publicKeyCredentialSource);
        $webauthnKey->save();

        $this->forceAuthenticate($user);

        Event::dispatch(new WebauthnRegister($webauthnKey));

        return $webauthnKey;
    }

    /**
     * @param User $user
     * @return PublicKeyCredentialRequestOptions
     */
    public function getAuthenticateData(User $user) : PublicKeyCredentialRequestOptions
    {
        return $this->app->make(PublicKeyCredentialRequestOptionsFactory::class)
            ->create($this->getRegisteredKeys($user));
    }

    /**
     * List of registered PublicKeyCredentialDescriptor classes associated to the user.
     * @param User $user
     * @return PublicKeyCredentialRequestOptions[]
     */
    private function getRegisteredKeys(User $user): array
    {
        return WebauthnKey::where('user_id', $user->getAuthIdentifier())
            ->get()
            ->map(function ($webauthnKey) {
                return $webauthnKey->getPublicKeyCredentialSource()->getPublicKeyCredentialDescriptor();
            })
            ->toArray();
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
            $this->forceAuthenticate();

            Event::dispatch(new WebauthnLogin($user));

            return true;
        }

        return false;
    }

    /**
     * @param User $user
     */
    public function forceAuthenticate(User $user)
    {
        $this->session->put([$this->config->get('webauthn.sessionName') => true]);
    }

    /**
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
        return $this->config->get('webauthn.enable') &&
            WebauthnKey::where('user_id', $user->getAuthIdentifier())->count() > 0;
    }
}
