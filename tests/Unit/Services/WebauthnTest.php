<?php

namespace LaravelWebauthn\Tests\Unit\Services;

use CBOR\ListObject;
use CBOR\MapItem;
use CBOR\MapObject;
use CBOR\NegativeIntegerObject;
use CBOR\TextStringObject;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use LaravelWebauthn\Actions\PrepareAssertionData;
use LaravelWebauthn\Actions\PrepareCreationData;
use LaravelWebauthn\Actions\ValidateKeyCreation;
use LaravelWebauthn\Models\WebauthnKey;
use LaravelWebauthn\Services\Webauthn;
use LaravelWebauthn\Tests\FeatureTestCase;
use ParagonIE\ConstantTime\Base64UrlSafe;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Uid\NilUuid;
use Symfony\Component\Uid\Uuid;
use Webauthn\AuthenticatorData;
use Webauthn\PublicKeyCredentialSource;

class WebauthnTest extends FeatureTestCase
{
    use DatabaseTransactions;

    #[Test]
    public function test_get_register_data()
    {
        $user = $this->signIn();

        $publicKey = $this->app[PrepareCreationData::class]($user);

        $this->assertInstanceOf(\LaravelWebauthn\Services\Webauthn\PublicKeyCredentialCreationOptions::class, $publicKey);

        $this->assertNotNull($publicKey->data->challenge);
        $this->assertEquals(32, strlen($publicKey->data->challenge));

        $this->assertInstanceOf(\Webauthn\PublicKeyCredentialUserEntity::class, $publicKey->data->user);
        $this->assertEquals($user->getAuthIdentifier(), $publicKey->data->user->id);
        $this->assertEquals($user->email, $publicKey->data->user->displayName);
    }

    #[Test]
    public function test_do_register_data()
    {
        $user = $this->signIn();

        $publicKey = $this->app[PrepareCreationData::class]($user);
        $this->assertInstanceOf(\LaravelWebauthn\Services\Webauthn\PublicKeyCredentialCreationOptions::class, $publicKey);

        $data = $this->getAttestationData($publicKey);

        $this->app[ValidateKeyCreation::class]($user, $data, 'name');

        $this->assertDatabaseHas('webauthn_keys', [
            'user_id' => $user->getAuthIdentifier(),
            'name' => 'name',
            'credentialId' => 'MA==',
            'type' => 'public-key',
            'transports' => '[]',
            'attestationType' => 'none',
            'trustPath' => '{}',
            'aaguid' => '30303030-3030-3030-3030-303030303030',
            'credentialPublicKey' => 'omExZXZhbHVlYTMm',
            'counter' => '1',
        ]);
    }

    #[Test]
    public function test_get_authenticate_data()
    {
        config(['webauthn.timeout' => 60000]);

        $user = $this->signIn();
        factory(WebauthnKey::class)->create([
            'user_id' => $user->getAuthIdentifier(),
        ]);

        $publicKey = $this->app[PrepareAssertionData::class]($user);

        $this->assertInstanceOf(\LaravelWebauthn\Services\Webauthn\PublicKeyCredentialRequestOptions::class, $publicKey);

        $this->assertNotNull($publicKey->data->challenge);
        $this->assertEquals(32, strlen($publicKey->data->challenge));

        $this->assertEquals('preferred', $publicKey->data->userVerification);
        $this->assertEquals('localhost', $publicKey->data->rpId);
        $this->assertEquals(60000, $publicKey->data->timeout);
        $this->assertCount(0, $publicKey->data->extensions);
    }

    #[Test]
    public function test_wrong_do_authenticate()
    {
        $user = $this->signIn();
        factory(WebauthnKey::class)->create([
            'user_id' => $user->getAuthIdentifier(),
            'credentialId' => '0',
        ]);

        $publicKey = $this->app[PrepareAssertionData::class]($user);
        $this->assertInstanceOf(\LaravelWebauthn\Services\Webauthn\PublicKeyCredentialRequestOptions::class, $publicKey);

        $data = $this->getAttestationData($publicKey);

        $this->expectException(\LaravelWebauthn\Exceptions\ResponseMismatchException::class);
        Webauthn::validateAssertion($user, $data);
    }

    private function getAttestationData($publicKey)
    {
        return [
            'id' => Base64UrlSafe::encodeUnpadded('0'),
            'rawId' => Base64UrlSafe::encode('0'),
            'type' => 'public-key',
            'response' => [
                'clientDataJSON' => Base64UrlSafe::encodeUnpadded(json_encode([
                    'type' => 'webauthn.create',
                    'challenge' => Base64UrlSafe::encodeUnpadded($publicKey->data->challenge),
                    'origin' => 'https://localhost',
                    'tokenBinding' => [
                        'status' => 'supported',
                        'id' => Base64UrlSafe::encodeUnpadded(1),
                    ],
                ])),
                'attestationObject' => Base64UrlSafe::encodeUnpadded((string) (new MapObject([
                    new MapItem(
                        new TextStringObject('authData'),
                        new TextStringObject(
                            hash('sha256', 'localhost', true). // rp_id_hash
                            pack('C', AuthenticatorData::FLAG_AT | AuthenticatorData::FLAG_UP). // flags
                            pack('N', 1). // signCount
                            '0000000000000000'. // aaguid
                            pack('n', 1).'0'. // credentialLength
                            ((string) new MapObject([
                                new MapItem(
                                    new TextStringObject('1'),
                                    new TextStringObject('value')
                                ),
                                new MapItem(
                                    new TextStringObject('3'),
                                    new NegativeIntegerObject(6, null)
                                ),
                            ])) // credentialPublicKey
                        )
                    ),
                    new MapItem(new TextStringObject('fmt'), new TextStringObject('none')),
                    new MapItem(new TextStringObject('attStmt'), new ListObject([])),
                ]))),
            ],
        ];
    }

    #[Test]
    public function test_force_authenticate()
    {
        $this->assertFalse($this->app[Webauthn::class]->check());

        $this->app[Webauthn::class]->login(null);

        $this->assertTrue($this->app[Webauthn::class]->check());
    }

    #[Test]
    public function test_enabled()
    {
        $user = $this->signIn();

        $this->assertFalse($this->app[Webauthn::class]->enabled($user));

        factory(WebauthnKey::class)->create([
            'user_id' => $user->getAuthIdentifier(),
        ]);

        $this->assertTrue($this->app[Webauthn::class]->enabled($user));
    }

    #[Test]
    public function test_aaguid_null()
    {
        $webauthnKey = new WebauthnKey;
        $webauthnKey->aaguid = null;

        $this->assertNull($webauthnKey->getAttributeValue('aaguid'));
        $this->assertNull($webauthnKey->aaguid);
    }

    #[Test]
    public function test_aaguid_empty()
    {
        $webauthnKey = new WebauthnKey;
        $webauthnKey->aaguid = '';

        $this->assertEquals('', $webauthnKey->getAttributeValue('aaguid'));
        $this->assertEquals('', $webauthnKey->aaguid);
    }

    #[Test]
    public function test_aaguid_string()
    {
        $webauthnKey = new WebauthnKey;
        $webauthnKey->aaguid = '38195f59-0e5b-4ebf-be46-75664177eeee';

        $this->assertEquals('38195f59-0e5b-4ebf-be46-75664177eeee', $webauthnKey->getAttributeValue('aaguid'));
        $this->assertInstanceOf(\Symfony\Component\Uid\AbstractUid::class, $webauthnKey->aaguid);
        $this->assertEquals(Uuid::fromString('38195f59-0e5b-4ebf-be46-75664177eeee'), $webauthnKey->aaguid);
    }

    #[Test]
    public function test_aaguid_uuid()
    {
        $webauthnKey = new WebauthnKey;
        $webauthnKey->aaguid = Uuid::fromString('38195f59-0e5b-4ebf-be46-75664177eeee');

        $this->assertEquals('38195f59-0e5b-4ebf-be46-75664177eeee', $webauthnKey->getAttributeValue('aaguid'));
        $this->assertInstanceOf(\Symfony\Component\Uid\AbstractUid::class, $webauthnKey->aaguid);
        $this->assertEquals(Uuid::fromString('38195f59-0e5b-4ebf-be46-75664177eeee'), $webauthnKey->aaguid);
    }

    #[Test]
    public function it_creates_model()
    {
        config(['webauthn.model' => WebauthnKeyTest::class]);

        $user = $this->user();

        $source = new PublicKeyCredentialSource(
            'test',
            'type',
            [],
            'attestationType',
            new \Webauthn\TrustPath\EmptyTrustPath,
            new NilUuid,
            'credentialPublicKey',
            $user->id,
            0
        );

        $webauthnkey = Webauthn::create($user, 'name', $source);

        $this->assertInstanceOf(WebauthnKey::class, $webauthnkey);
    }

    #[Test]
    public function it_creates_model_anyway()
    {
        config(['webauthn.model' => \stdClass::class]);

        $user = $this->user();

        $source = new PublicKeyCredentialSource(
            'test',
            'type',
            [],
            'attestationType',
            new \Webauthn\TrustPath\EmptyTrustPath,
            new NilUuid,
            'credentialPublicKey',
            $user->id,
            0
        );

        $this->expectException(ModelNotFoundException::class);
        Webauthn::create($user, 'name', $source);
    }
}

class WebauthnKeyTest extends WebauthnKey {}
