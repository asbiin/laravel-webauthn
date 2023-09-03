<?php

namespace LaravelWebauthn\Tests\Unit\Models;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use LaravelWebauthn\Models\WebauthnKey;
use LaravelWebauthn\Tests\FeatureTestCase;

class UserTest extends FeatureTestCase
{
    use DatabaseTransactions;

    /**
     * @test
     */
    public function it_get_keys()
    {
        $user = $this->user();

        $webauthnKey = factory(WebauthnKey::class)->create([
            'user_id' => $user->getAuthIdentifier(),
            'credentialId' => '1',
        ]);

        $keys = $user->webauthnKeys()->get();

        $this->assertCount(1, $keys);
        $this->assertEquals($webauthnKey->user_id, $keys->first()->user_id);
    }
}
