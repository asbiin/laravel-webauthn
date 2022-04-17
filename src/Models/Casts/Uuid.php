<?php

namespace LaravelWebauthn\Models\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Symfony\Component\Uid\Uuid as UuidConvert;
use Symfony\Component\Uid\AbstractUid;

class Uuid implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return \Symfony\Component\Uid\AbstractUid|null
     */
    public function get($model, $key, $value, $attributes): ?AbstractUid
    {
        if ($value !== null && UuidConvert::isValid($value)) {
            return UuidConvert::fromString($value);
        }

        return null;
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
        return (string) $value;
    }
}
