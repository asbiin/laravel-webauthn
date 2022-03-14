<?php

$factory->define(\LaravelWebauthn\Tests\User::class, function (Faker\Generator $faker) {
    return [
        'name' => $faker->name,
        'email' => $faker->email,
        'password' => $faker->word,
        'email_verified_at' => null,
        'remember_token' => null,
    ];
});
