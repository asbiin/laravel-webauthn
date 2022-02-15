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
     *
     * @param  \Illuminate\Auth\Events\Login  $event
     * @return void
     */
    public function handle(Login $event)
    {
        if (Auth::viaRemember()) {
            $this->registerWebauthn($event->user);
        }
    }

    /**
     * Force register Webauthn login.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     */
    private function registerWebauthn(User $user)
    {
        if (Webauthn::enabled($user)) {
            Webauthn::login($user);
        }
    }
}
