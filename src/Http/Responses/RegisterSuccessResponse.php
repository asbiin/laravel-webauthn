<?php

namespace LaravelWebauthn\Http\Responses;

use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Response;
use LaravelWebauthn\Contracts\RegisterSuccessResponse as RegisterSuccessResponseContract;
use LaravelWebauthn\Models\WebauthnKey;
use LaravelWebauthn\Services\Webauthn;

class RegisterSuccessResponse implements RegisterSuccessResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        $webauthnKey = $this->getWebauthnKey($request);

        return $request->wantsJson()
            ? $this->jsonResponse($request, $webauthnKey)
            : Redirect::intended(Webauthn::redirects('register'));
    }

    /**
     * Get the created WebauthnKey.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return WebauthnKey
     */
    protected function getWebauthnKey($request): WebauthnKey
    {
        $webauthnId = $this->webauthnId($request);

        return WebauthnKey::where('user_id', $request->user()->getAuthIdentifier())
            ->findOrFail($webauthnId);
    }

    /**
     * Get the id of the registerd key.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return int
     */
    protected function webauthnId($request)
    {
        return $request->session()->pull(Webauthn::SESSION_WEBAUTHNID_CREATED);
    }

    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  WebauthnKey  $webauthnKey
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function jsonResponse($request, WebauthnKey $webauthnKey)
    {
        return Response::json([
            'result' => true,
            'id' => $webauthnKey->id,
            'object' => 'webauthnKey',
            'name' => $webauthnKey->name,
            'counter' => $webauthnKey->counter,
        ], 201);
    }
}
