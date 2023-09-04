<?php

namespace LaravelWebauthn\Tests\Unit\Services\Webauthn;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use LaravelWebauthn\Models\WebauthnKey;
use LaravelWebauthn\Services\Webauthn\CredentialRepository;
use LaravelWebauthn\Tests\FeatureTestCase;

class CredentialRepositoryTest extends FeatureTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_returns_an_empty_array_when_no_keys_are_registered()
    {
        $user = $this->user();

        $this->assertEmpty(WebauthnKey::all());

        $this->assertEquals([], CredentialRepository::getRegisteredKeys($user));
    }

    /** @test */
    public function it_returns_an_array_with_the_keys()
    {
        $user = $this->user();

        $this->assertEmpty(WebauthnKey::all());

        $webauthnKey = factory(WebauthnKey::class)->create([
            'user_id' => $user->getAuthIdentifier(),
            'credentialId' => '1',
        ]);

        $keys = CredentialRepository::getRegisteredKeys($user);
        $this->assertCount(1, $keys);
        $this->assertEquals('{"type":"public-key","id":"MQ"}', json_encode($keys[0], JSON_THROW_ON_ERROR));
    }
}
