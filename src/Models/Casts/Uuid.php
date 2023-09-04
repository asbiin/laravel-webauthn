<?php

namespace LaravelWebauthn\Models\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Uid\AbstractUid;
use Symfony\Component\Uid\Uuid as UuidConvert;

/**
 * @implements CastsAttributes<AbstractUid,string>
 */
class Uuid implements CastsAttributes
{
    /**
     * Cast the given value.
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?AbstractUid
    {
        if ($value !== null && UuidConvert::isValid($value)) {
            return UuidConvert::fromString($value);
        }

        return null;
    }

    /**
     * Prepare the given value for storage.
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return (string) $value;
    }
}
