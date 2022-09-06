<?php

use ParagonIE\ConstantTime\Base64UrlSafe;
use Symfony\Component\Uid\Uuid;

$factory->define(\LaravelWebauthn\Models\WebauthnKey::class, function (Faker\Generator $faker) {
    return [
        'user_id' => function (array $data) {
            return factory(\LaravelWebauthn\Tests\User::class)->create()->id;
        },
        'name' => $faker->word,
        'counter' => 0,
        'credentialId' => function (array $data) {
            return Base64UrlSafe::encodeUnpadded($data['user_id']);
        },
        'type' => 'public-key',
        'transports' => [],
        'attestationType' => 'none',
        'trustPath' => new \Webauthn\TrustPath\EmptyTrustPath,
        'aaguid' => Uuid::fromString('38195f59-0e5b-4ebf-be46-75664177eeee'),
        'credentialPublicKey' => 'oWNrZXlldmFsdWU=',
    ];
});
