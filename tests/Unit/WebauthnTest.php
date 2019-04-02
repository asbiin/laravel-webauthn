<?php

namespace LaravelWebauthn\Tests\Unit;

use LaravelWebauthn\Webauthn;
use LaravelWebauthn\Models\WebauthnKey;
use LaravelWebauthn\Tests\FeatureTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class WebauthnTest extends FeatureTestCase
{
    use DatabaseTransactions;

    public function test_get_register_data()
    {
        $user = $this->signIn();
        factory(WebauthnKey::class)->create([
            'user_id' => $user->getAuthIdentifier(),
        ]);

        $publicKey = $this->app->make(Webauthn::class)->getRegisterData($user);

        $this->assertInstanceOf(\Webauthn\PublicKeyCredentialCreationOptions::class, $publicKey);

        $this->assertNotNull($publicKey->getChallenge());
        $this->assertEquals(32, strlen($publicKey->getChallenge()));

        $this->assertInstanceOf(\Webauthn\PublicKeyCredentialUserEntity::class, $publicKey->getUser());
        $this->assertEquals('0', $publicKey->getUser()->getId());
        $this->assertEquals('john@doe.com', $publicKey->getUser()->getDisplayName());
    }

    public function test_do_register_data()
    {
        $user = $this->signIn();
        factory(WebauthnKey::class)->create([
            'user_id' => $user->getAuthIdentifier(),
        ]);

        $publicKey = $this->app->make(Webauthn::class)->getRegisterData($user);
        $this->assertInstanceOf(\Webauthn\PublicKeyCredentialCreationOptions::class, $publicKey);

        $this->app->make(Webauthn::class)->doRegister($user, $publicKey, '[]', 'name');
    }
}
