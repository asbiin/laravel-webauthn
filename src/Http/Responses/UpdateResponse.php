<?php

namespace LaravelWebauthn\Http\Responses;

use Illuminate\Support\Facades\Response;
use LaravelWebauthn\Contracts\UpdateResponse as UpdateResponseContract;

class UpdateResponse implements UpdateResponseContract
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
            ? Response::noContent()
            : back()->with('status', 'webauthn-updated');
    }
}
