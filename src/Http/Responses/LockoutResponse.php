<?php

namespace LaravelWebauthn\Http\Responses;

use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use LaravelWebauthn\Contracts\LockoutResponse as LockoutResponseContract;
use LaravelWebauthn\Services\LoginRateLimiter;
use LaravelWebauthn\Services\Webauthn;

class LockoutResponse implements LockoutResponseContract
{
    /**
     * Create a new response instance.
     */
    public function __construct(
        protected LoginRateLimiter $limiter
    ) {
    }

    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        $seconds = $this->limiter->availableIn($request);
        throw ValidationException::withMessages([
            Webauthn::username() => [
                trans('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => ceil($seconds / 60),
                ]),
            ],
        ])->status(Response::HTTP_TOO_MANY_REQUESTS);
    }
}
