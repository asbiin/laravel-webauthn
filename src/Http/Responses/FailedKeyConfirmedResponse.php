<?php

namespace LaravelWebauthn\Http\Responses;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use LaravelWebauthn\Contracts\FailedKeyConfirmedResponse as FailedKeyConfirmedResponseContract;
use Symfony\Component\HttpFoundation\Response;

class FailedKeyConfirmedResponse implements FailedKeyConfirmedResponseContract
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
        $message = __('Invalid key.');

        if ($request->wantsJson()) {
            throw ValidationException::withMessages([
                'key' => [$message],
            ]);
        }

        return back()->withErrors(['key' => $message]);
    }
}
