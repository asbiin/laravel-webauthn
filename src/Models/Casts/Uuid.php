<?php

namespace LaravelWebauthn\Models\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Symfony\Component\Uid\AbstractUid;
use Symfony\Component\Uid\Uuid as UuidConvert;

/**
 * @implements CastsAttributes<AbstractUid,string>
 */
class Uuid implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  mixed  $value
     */
    public function get($model, string $key, $value, array $attributes): ?AbstractUid
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
     * @param  string|null  $value
     */
    public function set($model, string $key, mixed $value, array $attributes): ?string
    {
        return (string) $value;
    }
}
