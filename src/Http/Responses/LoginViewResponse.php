<?php

namespace LaravelWebauthn\Http\Responses;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Support\Facades\Response;
use LaravelWebauthn\Contracts\LoginViewResponse as LoginViewResponseContract;
use LaravelWebauthn\Services\Webauthn;
use Webauthn\PublicKeyCredentialRequestOptions;

class LoginViewResponse implements LoginViewResponseContract
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

        $view = $this->config->get('webauthn.views.authenticate', '');

        return $request->wantsJson()
            ? Response::json(['publicKey' => $publicKey])
            : Response::view($view, ['publicKey' => $publicKey]);
    }

    /**
     * Get public key request data.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Webauthn\PublicKeyCredentialRequestOptions
     */
    protected function publicKeyRequest($request): PublicKeyCredentialRequestOptions
    {
        return $request->session()->get(Webauthn::SESSION_PUBLICKEY_REQUEST);
    }
}
