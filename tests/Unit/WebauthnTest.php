<?php

namespace LaravelWebauthn\Tests\Unit;

use LaravelWebauthn\Webauthn;
use LaravelWebauthn\Tests\FeatureTestCase;

class WebauthnTest extends FeatureTestCase
{
    public function test_get_register_data()
    {
        $user = $this->signIn();

        $publicKey = $this->app->make(Webauthn::class)->getRegisterData($user);

        $this->assertInstanceOf(\Webauthn\PublicKeyCredentialCreationOptions::class, $publicKey);

        $this->assertNotNull($publicKey->getChallenge());
        $this->assertEquals(32, strlen($publicKey->getChallenge()));

        $this->assertInstanceOf(\Webauthn\PublicKeyCredentialUserEntity::class, $publicKey->getUser());
        $this->assertEquals('auth-identifier', $publicKey->getUser()->getId());
        $this->assertEquals('john@doe.com', $publicKey->getUser()->getDisplayName());
    }
}
