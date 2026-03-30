<?php

use Faker\Generator;
use LaravelWebauthn\Tests\User;

$factory->define(User::class, function (Generator $faker) {
    return [
        'name' => $faker->name,
        'email' => $faker->email,
        'password' => $faker->word,
        'email_verified_at' => null,
        'remember_token' => null,
    ];
});
