<?php

use Faker\Generator;
use LaravelWebauthn\Models\WebauthnKey;
use LaravelWebauthn\Tests\User;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Symfony\Component\Uid\Uuid;
use Webauthn\TrustPath\EmptyTrustPath;

$factory->define(WebauthnKey::class, function (Generator $faker) {
    return [
        'user_id' => function (array $data) {
            return factory(User::class)->create()->id;
        },
        'name' => $faker->word,
        'counter' => 0,
        'credentialId' => function (array $data) {
            return Base64UrlSafe::encodeUnpadded($data['user_id']);
        },
        'type' => 'public-key',
        'transports' => [],
        'attestationType' => 'none',
        'trustPath' => new EmptyTrustPath,
        'aaguid' => Uuid::fromString('38195f59-0e5b-4ebf-be46-75664177eeee'),
        'credentialPublicKey' => 'oWNrZXlldmFsdWU=',
    ];
});
