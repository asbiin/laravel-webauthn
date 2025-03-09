<?php

namespace LaravelWebauthn\Services;

use JsonSerializable;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @template T
 */
class JsonWrapper implements JsonSerializable
{
    /**
     * Create a JsoWrapper
     *
     * @param  T  $data
     */
    public function __construct(
        public mixed $data
    ) {}

    /**
     * Convert the object to its JSON representation.
     */
    #[\Override]
    public function jsonSerialize(): array
    {
        return json_decode((string) $this, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Convert the object to its string representation.
     */
    #[\Override]
    public function __toString()
    {
        return app(SerializerInterface::class)->serialize($this->data, 'json', [
            AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
            JsonEncode::OPTIONS => JSON_THROW_ON_ERROR,
        ]);
    }

    /**
     * Dynamically handle calls to the class.
     *
     * @throws \BadMethodCallException
     */
    public function __call(string $method, array $parameters): mixed
    {
        return call_user_func($method, [$this->data, ...$parameters]);
    }
}
