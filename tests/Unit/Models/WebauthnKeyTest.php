<?php

namespace LaravelWebauthn\Tests\Unit\Models;

use LaravelWebauthn\Models\WebauthnKey;
use LaravelWebauthn\Tests\FeatureTestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Uid\Uuid;
use Webauthn\CredentialRecord;
use Webauthn\TrustPath\EmptyTrustPath;

class WebauthnKeyTest extends FeatureTestCase
{
    #[Test]
    public function test_serialize_data()
    {
        $webauthnKey = new WebauthnKey;
        $webauthnKey->user_id = 0;
        $webauthnKey->publicKeyCredentialSource = new CredentialRecord('a', 'b', [], 'c', new EmptyTrustPath, Uuid::fromString('38195f59-0e5b-4ebf-be46-75664177eeee'), 'e', '0', 1);

        $this->assertEquals(0, $webauthnKey->user_id);
        $this->assertEquals('a', $webauthnKey->credentialId);
        $this->assertEquals('b', $webauthnKey->type);
        $this->assertEquals([], $webauthnKey->transports);
        $this->assertEquals('38195f59-0e5b-4ebf-be46-75664177eeee', $webauthnKey->aaguid);
        $this->assertEquals('e', $webauthnKey->credentialPublicKey);
        $this->assertEquals(1, $webauthnKey->counter);
        $this->assertEquals('c', $webauthnKey->attestationType);
        $this->assertInstanceOf(EmptyTrustPath::class, $webauthnKey->trustPath);
    }

    #[Test]
    public function test_deserialize_data()
    {
        $webauthnKey = new WebauthnKey;

        $webauthnKey->user_id = 0;
        $webauthnKey->credentialId = 'a';
        $webauthnKey->type = 'b';
        $webauthnKey->transports = [];
        $webauthnKey->aaguid = Uuid::fromString('38195f59-0e5b-4ebf-be46-75664177eeee');
        $webauthnKey->credentialPublicKey = 'e';
        $webauthnKey->counter = 0;
        $webauthnKey->attestationType = 'c';
        $webauthnKey->trustPath = new EmptyTrustPath;

        $publicKeyCredentialSource = $webauthnKey->publicKeyCredentialSource;

        $this->assertEquals('a', $publicKeyCredentialSource->publicKeyCredentialId);
        $this->assertEquals('b', $publicKeyCredentialSource->type);
        $this->assertEquals([], $publicKeyCredentialSource->transports);
        $this->assertEquals('38195f59-0e5b-4ebf-be46-75664177eeee', $publicKeyCredentialSource->aaguid);
        $this->assertEquals('e', $publicKeyCredentialSource->credentialPublicKey);
        $this->assertEquals('0', $publicKeyCredentialSource->userHandle);
        $this->assertEquals(0, $publicKeyCredentialSource->counter);
        $this->assertEquals('c', $publicKeyCredentialSource->attestationType);
        $this->assertInstanceOf(EmptyTrustPath::class, $publicKeyCredentialSource->trustPath);
    }
}
