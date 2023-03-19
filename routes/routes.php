<?php

use Illuminate\Support\Facades\Route;
use LaravelWebauthn\Http\Controllers\AuthenticateController;
use LaravelWebauthn\Http\Controllers\WebauthnKeyController;

$limiterMiddleware = ($limiter = config('webauthn.limiters.login')) !== null
    ? 'throttle:'.$limiter
    : null;

Route::group([
    'middleware' => array_filter([
        config('webauthn.guard', 'web'),
        $limiterMiddleware,
    ]),
], function () {
    // Authentication
    if (config('webauthn.views.authenticate') !== null) {
        Route::get('auth', [AuthenticateController::class, 'create'])->name('webauthn.login');
    }
    Route::post('auth/options', [AuthenticateController::class, 'create'])->name('webauthn.auth.options');
    Route::post('auth', [AuthenticateController::class, 'store'])->name('webauthn.auth');
});

Route::group([
    'middleware' => array_filter(array_merge(
        config('webauthn.middleware', ['web']),
        [
            config('webauthn.auth_middleware', 'auth').':'.config('webauthn.guard', 'web'),
        ]
    )),
], function () {
    // Webauthn key registration
    if (config('webauthn.views.register') !== null) {
        Route::get('keys/create', [WebauthnKeyController::class, 'create'])->name('webauthn.create');
    }
    Route::post('keys/options', [WebauthnKeyController::class, 'create'])->name('webauthn.store.options');
    Route::post('keys', [WebauthnKeyController::class, 'store'])->name('webauthn.store');
    Route::delete('keys/{id}', [WebauthnKeyController::class, 'destroy'])->name('webauthn.destroy');
    Route::put('keys/{id}', [WebauthnKeyController::class, 'update'])->name('webauthn.update');
});
