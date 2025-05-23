<?php

namespace LaravelWebauthn\Tests\Unit\Auth;

use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use LaravelWebauthn\Auth\EloquentWebAuthnProvider;
use LaravelWebauthn\Facades\Webauthn;
use LaravelWebauthn\Models\WebauthnKey;
use LaravelWebauthn\Tests\FeatureTestCase;
use LaravelWebauthn\Tests\User;
use ParagonIE\ConstantTime\Base64UrlSafe;
use PHPUnit\Framework\Attributes\Test;

class EloquentWebAuthnProviderTest extends FeatureTestCase
{
    use DatabaseTransactions;

    #[Test]
    public function it_validate_credentials()
    {
        $user = $this->user();

        Webauthn::shouldReceive('validateAssertion')->andReturn(true);

        $provider = new EloquentWebAuthnProvider(
            app('config'),
            app(Hasher::class),
            ''
        );

        $result = $provider->validateCredentials($user, [
            'id' => 'id',
            'rawId' => 'rawId',
            'type' => 'type',
            'response' => 'response',
        ]);

        $this->assertTrue($result);
    }

    #[Test]
    public function it_retrieve_user()
    {
        $user = $this->signin();

        factory(WebauthnKey::class)->create([
            'user_id' => $user->getAuthIdentifier(),
            'credentialId' => 'id',
        ]);

        Webauthn::shouldReceive('validateAssertion')->andReturn(true);
        Webauthn::shouldReceive('model')->andReturn(WebauthnKey::class);

        $provider = new EloquentWebAuthnProvider(
            app('config'),
            app(Hasher::class),
            User::class,
        );

        $result = $provider->retrieveByCredentials([
            'id' => Base64UrlSafe::encode('id'),
            'rawId' => 'rawId',
            'type' => 'public-key',
            'response' => 'response',
        ]);

        $this->assertEquals($user->id, $result->id);
    }

    #[Test]
    public function it_retrieve_user_old_format()
    {
        $user = $this->signin();
        $this->assertEquals($user->id, 1);

        factory(WebauthnKey::class)->create([
            'user_id' => $user->getAuthIdentifier(),
            'credentialId' => '1',
        ]);

        Webauthn::shouldReceive('validateAssertion')->andReturn(true);
        Webauthn::shouldReceive('model')->andReturn(WebauthnKey::class);

        $provider = new EloquentWebAuthnProvider(
            app('config'),
            app(Hasher::class),
            User::class,
        );

        $result = $provider->retrieveByCredentials([
            'id' => 'MQ==',
            'rawId' => 'rawId',
            'type' => 'public-key',
            'response' => 'response',
        ]);

        $this->assertNotNull($result);
        $this->assertEquals($user->id, $result->id);
    }

    #[Test]
    public function it_retrieve_user_new_format()
    {
        $user = $this->signin();
        $this->assertEquals($user->id, 1);
        $this->assertEquals($user->id, 1);

        factory(WebauthnKey::class)->create([
            'user_id' => $user->getAuthIdentifier(),
            'credentialId' => '1',
        ]);

        Webauthn::shouldReceive('validateAssertion')->andReturn(true);
        Webauthn::shouldReceive('model')->andReturn(WebauthnKey::class);

        $provider = new EloquentWebAuthnProvider(
            app('config'),
            app(Hasher::class),
            User::class,
        );

        $result = $provider->retrieveByCredentials([
            'id' => 'MQ',
            'rawId' => 'rawId',
            'type' => 'public-key',
            'response' => 'response',
        ]);

        $this->assertNotNull($result);
        $this->assertEquals($user->id, $result->id);
    }

    #[Test]
    public function it_does_not_fail_when_retrieving_user()
    {
        Webauthn::shouldReceive('validateAssertion')->andReturn(true);
        Webauthn::shouldReceive('model')->andReturn(WebauthnKey::class);

        $provider = new EloquentWebAuthnProvider(
            app('config'),
            app(Hasher::class),
            User::class,
        );

        $result = $provider->retrieveByCredentials([
            'id' => Base64UrlSafe::encode('id'),
            'rawId' => 'rawId',
            'type' => 'public-key',
            'response' => 'response',
        ]);

        $this->assertNull($result);
    }
}
