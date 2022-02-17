<?php

namespace LaravelWebauthn\Services;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Database\Eloquent\Model;
use LaravelWebauthn\Models\WebauthnKey;
use Webauthn\PublicKeyCredentialSource;

abstract class WebauthnRepository
{
    /**
     * The callback that is responsible for creating a new webauthnkey, if applicable.
     *
     * @var callable|null
     */
    public static $createWebauthnkeyUsingCallback;

    /**
     * Create a new key.
     *
     * @param  User  $user
     * @param  string  $keyName
     * @param  PublicKeyCredentialSource  $publicKeyCredentialSource
     * @return mixed
     */
    public static function create(User $user, string $keyName, PublicKeyCredentialSource $publicKeyCredentialSource)
    {
        if (static::$createWebauthnkeyUsingCallback !== null) {
            return call_user_func(static::$createWebauthnkeyUsingCallback, [$user, $keyName, $publicKeyCredentialSource]);
        }

        $model = static::model();
        $webauthnKey = new $model;
        if ($webauthnKey instanceof Model) {
            $webauthnKey->forceFill([
                'user_id' => $user->getAuthIdentifier(),
                'name' => $keyName,
                'publicKeyCredentialSource' => $publicKeyCredentialSource,
            ]);
            $webauthnKey->save();
        }

        return $webauthnKey;
    }

    /**
     * Register a callback that is responsible for creating a new webauthnkey.
     *
     * @param  callable  $callback
     * @return void
     */
    public static function createWebauthnkeyUsing(callable $callback)
    {
        static::$createWebauthnkeyUsingCallback = $callback;
    }

    /**
     * Get the model for Webauthnkey.
     *
     * @return string
     */
    public static function model(): string
    {
        $model = config('webauthn.model', WebauthnKey::class);

        return '\\'.ltrim($model, '\\');
    }

    /**
     * Detect if user has a key.
     *
     * @param  User  $user
     * @return bool
     */
    public static function hasKey(User $user): bool
    {
        return (static::model())::where('user_id', $user->getAuthIdentifier())->count() > 0;
    }
}
