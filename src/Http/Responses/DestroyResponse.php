<?php

namespace LaravelWebauthn\Http\Responses;

use Illuminate\Support\Facades\Response;
use LaravelWebauthn\Contracts\DestroyResponse as DestroyResponseContract;

class DestroyResponse implements DestroyResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        return $request->wantsJson()
            ? Response::noContent()
            : back()->with('status', 'webauthn-destroyed');
    }
}
