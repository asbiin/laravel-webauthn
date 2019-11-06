<?php

namespace LaravelWebauthn\Tests\Unit;

use LaravelWebauthn\Models\WebauthnKey;
use LaravelWebauthn\Tests\FeatureTestCase;
use Ramsey\Uuid\Uuid;
use Webauthn\PublicKeyCredentialSource;

class WebauthnKeyTest extends FeatureTestCase
{
    public function test_serialize_data()
    {
        $webauthnKey = new WebauthnKey();
        $webauthnKey->user_id = 0;
        $webauthnKey->publicKeyCredentialSource = new PublicKeyCredentialSource('a', 'b', [], 'c', new \Webauthn\TrustPath\EmptyTrustPath(), Uuid::fromString('38195f59-0e5b-4ebf-be46-75664177eeee'), 'e', '0', 1);

        $this->assertEquals(0, $webauthnKey->user_id);
        $this->assertEquals('a', $webauthnKey->credentialId);
        $this->assertEquals('b', $webauthnKey->type);
        $this->assertEquals([], $webauthnKey->transports);
        $this->assertEquals('38195f59-0e5b-4ebf-be46-75664177eeee', $webauthnKey->aaguid->toString());
        $this->assertEquals('e', $webauthnKey->credentialPublicKey);
        $this->assertEquals(1, $webauthnKey->counter);
        $this->assertEquals('c', $webauthnKey->attestationType);
        $this->assertInstanceOf(\Webauthn\TrustPath\EmptyTrustPath::class, $webauthnKey->trustPath);
    }

    public function test_deserialize_data()
    {
        $webauthnKey = new WebauthnKey();

        $webauthnKey->user_id = 0;
        $webauthnKey->credentialId = 'a';
        $webauthnKey->type = 'b';
        $webauthnKey->transports = [];
        $webauthnKey->aaguid = Uuid::fromString('38195f59-0e5b-4ebf-be46-75664177eeee');
        $webauthnKey->credentialPublicKey = 'e';
        $webauthnKey->counter = 0;
        $webauthnKey->attestationType = 'c';
        $webauthnKey->trustPath = new \Webauthn\TrustPath\EmptyTrustPath();

        $publicKeyCredentialSource = $webauthnKey->publicKeyCredentialSource;

        $this->assertEquals('a', $publicKeyCredentialSource->getPublicKeyCredentialId());
        $this->assertEquals('b', $publicKeyCredentialSource->getType());
        $this->assertEquals([], $publicKeyCredentialSource->getTransports());
        $this->assertEquals('38195f59-0e5b-4ebf-be46-75664177eeee', $publicKeyCredentialSource->getAaguid()->toString());
        $this->assertEquals('e', $publicKeyCredentialSource->getCredentialPublicKey());
        $this->assertEquals('0', $publicKeyCredentialSource->getUserHandle());
        $this->assertEquals(0, $publicKeyCredentialSource->getCounter());
        $this->assertEquals('c', $publicKeyCredentialSource->getAttestationType());
        $this->assertInstanceOf(\Webauthn\TrustPath\EmptyTrustPath::class, $publicKeyCredentialSource->getTrustPath());
    }
}
