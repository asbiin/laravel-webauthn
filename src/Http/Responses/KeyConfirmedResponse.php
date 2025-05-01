<?php

namespace LaravelWebauthn\Http\Responses;

use Illuminate\Http\JsonResponse;
use LaravelWebauthn\Contracts\KeyConfirmedResponse as KeyConfirmedResponseContract;
use LaravelWebauthn\Services\Webauthn;

class KeyConfirmedResponse implements KeyConfirmedResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    #[\Override]
    public function toResponse($request)
    {
        return $request->wantsJson()
            ? new JsonResponse('', 201)
            : redirect()->intended(Webauthn::redirects('key-confirmation'));
    }
}
