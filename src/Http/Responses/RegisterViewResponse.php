<?php

namespace LaravelWebauthn\Http\Responses;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Support\Facades\Response;
use LaravelWebauthn\Contracts\RegisterViewResponse as RegisterViewResponseContract;
use LaravelWebauthn\Services\Webauthn;
use Webauthn\PublicKeyCredentialCreationOptions;

class RegisterViewResponse implements RegisterViewResponseContract
{
    /**
     * The config repository instance.
     *
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        $publicKey = $this->publicKeyRequest($request);

        $view = $this->config->get('webauthn.views.register', '');

        return $request->wantsJson()
            ? Response::json(['publicKey' => $publicKey, 'name'=>$request->input('name', 'key')])
            : Response::view($view, ['publicKey' => $publicKey, 'name'=>$request->input('name', 'key')]);
    }

    /**
     * Get public key creation data.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Webauthn\PublicKeyCredentialCreationOptions
     */
    protected function publicKeyRequest($request): PublicKeyCredentialCreationOptions
    {
        return $request->session()->get(Webauthn::SESSION_PUBLICKEY_CREATION);
    }
}
