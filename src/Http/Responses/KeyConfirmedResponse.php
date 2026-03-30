<?php

namespace LaravelWebauthn\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LaravelWebauthn\Contracts\KeyConfirmedResponse as KeyConfirmedResponseContract;
use LaravelWebauthn\Services\Webauthn;
use Symfony\Component\HttpFoundation\Response;

class KeyConfirmedResponse implements KeyConfirmedResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  Request  $request
     * @return Response
     */
    #[\Override]
    public function toResponse($request)
    {
        return $request->wantsJson()
            ? new JsonResponse('', 201)
            : redirect()->intended(Webauthn::redirects('key-confirmation'));
    }
}
