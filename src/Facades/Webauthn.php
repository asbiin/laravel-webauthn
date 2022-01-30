<?php

namespace LaravelWebauthn\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \LaravelWebauthn\Models\WebauthnKey create(\Illuminate\Contracts\Auth\Authenticatable $user, string $keyName, \Webauthn\PublicKeyCredentialSource $publicKeyCredentialSource)
 * @method static void login()
 * @method static void logout()
 * @method static bool check()
 * @method static bool webauthnEnabled()
 * @method static bool enabled(\Illuminate\Contracts\Auth\Authenticatable $user)
 * @method static bool canRegister(\Illuminate\Contracts\Auth\Authenticatable $user)
 * @method static bool hasKey(\Illuminate\Contracts\Auth\Authenticatable $user)
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
        return \LaravelWebauthn\Services\Webauthn::class;
    }
}
