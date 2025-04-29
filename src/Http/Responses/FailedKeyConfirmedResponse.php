<?php

namespace LaravelWebauthn\Http\Responses;

use Illuminate\Validation\ValidationException;
use LaravelWebauthn\Contracts\FailedKeyConfirmedResponse as FailedKeyConfirmedResponseContract;

class FailedKeyConfirmedResponse implements FailedKeyConfirmedResponseContract
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
        $message = __('Invalid key.');

        if ($request->wantsJson()) {
            throw ValidationException::withMessages([
                'key' => [$message],
            ]);
        }

        return back()->withErrors(['key' => $message]);
    }
}
