<?php

namespace LaravelWebauthn\Models\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Webauthn\TrustPath\TrustPath as TrustPathLib;
use Webauthn\TrustPath\TrustPathLoader;

/**
 * @implements CastsAttributes<TrustPathLib,string>
 */
class TrustPath implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  mixed  $value
     */
    public function get($model, string $key, $value, array $attributes): ?TrustPathLib
    {
        return $value !== null ? TrustPathLoader::loadTrustPath(json_decode($value, true, flags: JSON_THROW_ON_ERROR)) : null;
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string|null  $value
     */
    public function set($model, string $key, mixed $value, array $attributes): ?string
    {
        return json_encode($value, flags: JSON_THROW_ON_ERROR);
    }
}
