<?php

namespace LaravelWebauthn\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Support\Facades\Auth;
use LaravelWebauthn\Facades\Webauthn;

class LoginViaRemember
{
    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        if (Auth::viaRemember()) {
            $this->registerWebauthn($event->user);
        }
    }

    /**
     * Force register Webauthn login.
     */
    private function registerWebauthn(User $user)
    {
        if (Webauthn::enabled($user)) {
            Webauthn::login($user);
        }
    }
}
