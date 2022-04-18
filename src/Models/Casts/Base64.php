<?php

namespace LaravelWebauthn\Models\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Webauthn\Util\Base64 as Base64Webauthn;

class Base64 implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return string|null
     */
    public function get($model, $key, $value, $attributes): ?string
    {
        return $value !== null ? Base64Webauthn::decode($value) : null;
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return string|null
     */
    public function set($model, $key, $value, $attributes): ?string
    {
        return $value !== null ? Base64UrlSafe::encode($value) : null;
    }
}
