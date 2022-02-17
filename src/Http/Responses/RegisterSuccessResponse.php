<?php

namespace LaravelWebauthn\Http\Responses;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Response;
use LaravelWebauthn\Contracts\RegisterSuccessResponse as RegisterSuccessResponseContract;
use LaravelWebauthn\Facades\Webauthn;
use LaravelWebauthn\Models\WebauthnKey;

class RegisterSuccessResponse implements RegisterSuccessResponseContract
{
    /**
     * The new Webauthn key id.
     *
     * @var int
     */
    protected int $webauthnId;

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
    protected function getWebauthnKey(Request $request): WebauthnKey
    {
        return (Webauthn::model())::where('user_id', $request->user()->getAuthIdentifier())
            ->findOrFail($this->webauthnId);
    }

    /**
     * Get the id of the registerd key.
     *
     * @param  int  $webauthnId
     * @return self
     */
    public function setWebauthnId(Request $request, int $webauthnId): self
    {
        $this->webauthnId = $webauthnId;

        return $this;
    }

    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  WebauthnKey  $webauthnKey
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function jsonResponse(Request $request, WebauthnKey $webauthnKey): \Symfony\Component\HttpFoundation\Response
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
