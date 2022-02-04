<?php

namespace Database\Factories\LaravelWebauthn\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use LaravelWebauthn\Models\WebauthnKey;
use LaravelWebauthn\Tests\Authenticated;

class WebauthnKeyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = WebauthnKey::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => function (array $attributes) {
                $user = new Authenticated();
                $user->email = 'john@doe.com';
                return $user->getAuthIdentifier();
            },
            'name' => 'key',
            'credentialId' => 'credentialId',
            'type' => 'public-key',
            'transports' => 'transports',
            'attestationType' => 'attestationType',
            'trustPath' => 'trustPath',
            'aaguid' => 'aaguid',
            'credentialPublicKey' => 'credentialPublicKey',
            'counter' => 0,
        ];
    }
}
