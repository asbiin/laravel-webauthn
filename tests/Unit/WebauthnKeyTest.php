<?php

namespace LaravelWebauthn\Tests\Unit;

use LaravelWebauthn\Models\WebauthnKey;
use Webauthn\PublicKeyCredentialSource;
use LaravelWebauthn\Tests\FeatureTestCase;

class WebauthnKeyTest extends FeatureTestCase
{
    public function test_serialize_data()
    {
        $webauthnKey = new WebauthnKey();
        $webauthnKey->publicKeyCredentialSource = new PublicKeyCredentialSource('a', 'b', [], 'c', new \Webauthn\TrustPath\EmptyTrustPath(), 'd', 'e', 'f', 0);

        $this->assertEquals('a', $webauthnKey->credentialId);
        $this->assertEquals('b', $webauthnKey->type);
        $this->assertEquals([], $webauthnKey->transports);
        $this->assertEquals('d', $webauthnKey->aaguid);
        $this->assertEquals('e', $webauthnKey->credentialPublicKey);
        $this->assertEquals('f', $webauthnKey->userHandle);
        $this->assertEquals(0, $webauthnKey->counter);
        $this->assertEquals('c', $webauthnKey->attestationType);
        $this->assertInstanceOf(\Webauthn\TrustPath\EmptyTrustPath::class, $webauthnKey->trustPath);
    }

    public function test_deserialize_data()
    {
        $webauthnKey = new WebauthnKey();

        $webauthnKey->credentialId = 'a';
        $webauthnKey->type = 'b';
        $webauthnKey->transports = [];
        $webauthnKey->aaguid = 'd';
        $webauthnKey->credentialPublicKey = 'e';
        $webauthnKey->userHandle = 'f';
        $webauthnKey->counter = 0;
        $webauthnKey->attestationType = 'c';
        $webauthnKey->trustPath = new \Webauthn\TrustPath\EmptyTrustPath();

        $publicKeyCredentialSource = $webauthnKey->publicKeyCredentialSource;

        $this->assertEquals('a', $publicKeyCredentialSource->getPublicKeyCredentialId());
        $this->assertEquals('b', $publicKeyCredentialSource->getType());
        $this->assertEquals([], $publicKeyCredentialSource->getTransports());
        $this->assertEquals('d', $publicKeyCredentialSource->getAaguid());
        $this->assertEquals('e', $publicKeyCredentialSource->getCredentialPublicKey());
        $this->assertEquals('f', $publicKeyCredentialSource->getUserHandle());
        $this->assertEquals(0, $publicKeyCredentialSource->getCounter());
        $this->assertEquals('c', $publicKeyCredentialSource->getAttestationType());
        $this->assertInstanceOf(\Webauthn\TrustPath\EmptyTrustPath::class, $publicKeyCredentialSource->getTrustPath());
    }
}
