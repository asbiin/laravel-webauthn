<?php

use Ramsey\Uuid\Uuid;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/

$factory->define(\LaravelWebauthn\Models\WebauthnKey::class, function (Faker\Generator $faker) {
    return [
        'user_id' => '0',
        'name' => $faker->word,
        'counter' => 0,
        'credentialId' => 'MA==',
        'type' => 'public-key',
        'transports' => [],
        'attestationType' => 'none',
        'trustPath' => new \Webauthn\TrustPath\EmptyTrustPath,
        'aaguid' => Uuid::fromString('38195f59-0e5b-4ebf-be46-75664177eeee'),
        'credentialPublicKey' => 'oWNrZXlldmFsdWU=',
    ];
});
