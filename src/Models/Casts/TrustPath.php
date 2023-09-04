<?php

namespace LaravelWebauthn\Models\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Webauthn\TrustPath\TrustPath as TrustPathLib;
use Webauthn\TrustPath\TrustPathLoader;

/**
 * @implements CastsAttributes<TrustPathLib,string>
 */
class TrustPath implements CastsAttributes
{
    /**
     * Cast the given value.
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?TrustPathLib
    {
        return $value !== null ? TrustPathLoader::loadTrustPath(json_decode($value, true, flags: JSON_THROW_ON_ERROR)) : null;
    }

    /**
     * Prepare the given value for storage.
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return json_encode($value, flags: JSON_THROW_ON_ERROR);
    }
}
