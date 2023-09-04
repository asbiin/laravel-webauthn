<?php

namespace LaravelWebauthn\Models\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Webauthn\Util\Base64 as Base64Webauthn;

/**
 * @implements CastsAttributes<string, string>
 */
class Base64 implements CastsAttributes
{
    /**
     * Cast the given value.
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value !== null ? Base64Webauthn::decode($value) : null;
    }

    /**
     * Prepare the given value for storage.
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value !== null ? Base64UrlSafe::encode($value) : null;
    }
}
