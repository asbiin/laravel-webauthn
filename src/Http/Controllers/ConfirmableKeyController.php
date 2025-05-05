<?php

namespace LaravelWebauthn\Http\Controllers;

use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Date;
use LaravelWebauthn\Actions\ConfirmKey;
use LaravelWebauthn\Contracts\FailedKeyConfirmedResponse;
use LaravelWebauthn\Contracts\KeyConfirmedResponse;

class ConfirmableKeyController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected StatefulGuard $guard) {}

    /**
     * Confirm the user's key.
     */
    public function store(Request $request): Responsable
    {
        $confirmed = app(ConfirmKey::class)(
            $this->guard, $request
        );

        if ($confirmed) {
            $request->session()->put('auth.password_confirmed_at', Date::now()->unix());
        }

        return $confirmed
            ? app(KeyConfirmedResponse::class)
            : app(FailedKeyConfirmedResponse::class);
    }
}
