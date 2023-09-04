<?php

namespace LaravelWebauthn\Services;

use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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
     */
    public static function create(User $user, string $keyName, PublicKeyCredentialSource $publicKeyCredentialSource): Model
    {
        if (static::$createWebauthnkeyUsingCallback !== null) {
            return call_user_func(static::$createWebauthnkeyUsingCallback, [$user, $keyName, $publicKeyCredentialSource]);
        }

        $webauthnKey = static::createModel();
        $webauthnKey->forceFill([
            'user_id' => $user->getAuthIdentifier(),
            'name' => $keyName,
            'publicKeyCredentialSource' => $publicKeyCredentialSource,
        ]);
        $webauthnKey->save();

        return $webauthnKey;
    }

    /**
     * Register a callback that is responsible for creating a new webauthnkey.
     */
    public static function createWebauthnkeyUsing(callable $callback): void
    {
        static::$createWebauthnkeyUsingCallback = $callback;
    }

    /**
     * Get the model for Webauthnkey.
     */
    public static function model(): string
    {
        $model = config('webauthn.model', WebauthnKey::class);

        return '\\'.ltrim($model, '\\');
    }

    /**
     * Create a new model instance.
     */
    public static function createModel(): Model
    {
        $model = static::model();
        $webauthnKey = new $model;
        if (! $webauthnKey instanceof Model) {
            throw new ModelNotFoundException('Wrong model type: '.gettype($webauthnKey));
        }

        return $webauthnKey;
    }

    /**
     * Detect if user has a key.
     */
    public static function hasKey(User $user): bool
    {
        return (static::model())::where('user_id', $user->getAuthIdentifier())->count() > 0;
    }
}
