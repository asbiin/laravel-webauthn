<?php

namespace LaravelWebauthn\Tests\Unit\Services\Webauthn;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use LaravelWebauthn\Models\WebauthnKey;
use LaravelWebauthn\Services\Webauthn\CredentialRepository;
use LaravelWebauthn\Tests\FeatureTestCase;
use PHPUnit\Framework\Attributes\Test;

class CredentialRepositoryTest extends FeatureTestCase
{
    use DatabaseTransactions;

    #[Test]
    public function it_returns_an_empty_array_when_no_keys_are_registered()
    {
        $user = $this->user();

        $this->assertEmpty(WebauthnKey::all());

        $this->assertEquals([], CredentialRepository::getRegisteredKeys($user));
    }

    #[Test]
    public function it_returns_an_array_with_the_keys()
    {
        $user = $this->user();

        $this->assertEmpty(WebauthnKey::all());

        factory(WebauthnKey::class)->create([
            'user_id' => $user->getAuthIdentifier(),
            'credentialId' => '1',
        ]);

        $keys = CredentialRepository::getRegisteredKeys($user);
        $this->assertCount(1, $keys);
        $this->assertEquals('{"type":"public-key","id":"1","transports":[]}', json_encode($keys[0], JSON_THROW_ON_ERROR));
    }
}
