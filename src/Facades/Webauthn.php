<?php

namespace LaravelWebauthn\Facades;

use Illuminate\Support\Facades\Facade;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialCreationOptions;

/**
 * @method static PublicKeyCredentialCreationOptions getRegisterData(\Illuminate\Foundation\Auth\User $user)
 * @method static \LaravelWebauthn\Models\WebauthnKey doRegister(\Illuminate\Foundation\Auth\User $user, PublicKeyCredentialCreationOptions $publicKey, string $data, string $keyName)
 * @method static PublicKeyCredentialRequestOptions getAuthenticateData(\Illuminate\Foundation\Auth\User $user)
 * @method static bool doAuthenticate(\Illuminate\Foundation\Auth\User $user, PublicKeyCredentialRequestOptions $publicKey, string $data)
 * @method static void forceAuthenticate(\Illuminate\Foundation\Auth\User $user)
 * @method static bool check()
 * @method static void fireLoginEvent(\Illuminate\Contracts\Auth\Authenticatable $user)
 *
 * @see \LaravelWebauthn\Webauthn
 */
class Webauthn extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return \LaravelWebauthn\Webauthn::class;
    }
}
