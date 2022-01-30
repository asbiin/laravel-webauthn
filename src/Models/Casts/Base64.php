<?php

namespace LaravelWebauthn\Models\Casts;

use Base64Url\Base64Url;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

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
        return $value !== null ? Base64Url::decode($value) : null;
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
        return $value !== null ? Base64Url::encode($value) : null;
    }
}
