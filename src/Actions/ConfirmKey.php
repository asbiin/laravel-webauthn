<?php

namespace LaravelWebauthn\Actions;

use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\Request;
use LaravelWebauthn\Services\Webauthn;

class ConfirmKey
{
    /**
     * Confirm that the given challenge is valid for the given user.
     */
    public function __invoke(StatefulGuard $guard, Request $request): bool
    {
        return is_null(Webauthn::$confirmKeyUsingCallback)
            ? $guard->attempt($this->getParams($request))
            : $this->confirmKeyUsingCustomCallback($request);
    }

    /**
     * Confirm the give challenge using a custom callback.
     */
    protected function confirmKeyUsingCustomCallback(Request $request): bool
    {
        return call_user_func(
            Webauthn::$confirmKeyUsingCallback,
            $this->getParams($request)
        );
    }

    /**
     * Get the parameters from the request.
     */
    private function getParams(Request $request): array
    {
        return $request->only(['id', 'rawId', 'response', 'type']);
    }
}
