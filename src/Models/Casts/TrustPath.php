<?php

namespace LaravelWebauthn\Models\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Symfony\Component\Serializer\SerializerInterface;
use Webauthn\TrustPath\TrustPath as TrustPathLib;

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
    #[\Override]
    public function get($model, string $key, $value, array $attributes): ?TrustPathLib
    {
        return $value !== null ? app(SerializerInterface::class)->deserialize($value, TrustPathLib::class, 'json') : null;
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string|null  $value
     */
    #[\Override]
    public function set($model, string $key, mixed $value, array $attributes): ?string
    {
        return json_encode($value, flags: JSON_THROW_ON_ERROR);
    }
}
