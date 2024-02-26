<?php

namespace LaravelWebauthn\Tests\Unit\Services;

use CBOR\ListObject;
use CBOR\MapItem;
use CBOR\MapObject;
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
use Symfony\Component\Uid\NilUuid;
use Symfony\Component\Uid\Uuid;
use Webauthn\AuthenticatorData;
use Webauthn\PublicKeyCredentialSource;

class WebauthnTest extends FeatureTestCase
{
    use DatabaseTransactions;

    /**
     * @test
     */
    public function test_get_register_data()
    {
        $user = $this->signIn();

        $publicKey = $this->app[PrepareCreationData::class]($user);

        $this->assertInstanceOf(\Webauthn\PublicKeyCredentialCreationOptions::class, $publicKey);

        $this->assertNotNull($publicKey->getChallenge());
        $this->assertEquals(32, strlen($publicKey->getChallenge()));

        $this->assertInstanceOf(\Webauthn\PublicKeyCredentialUserEntity::class, $publicKey->getUser());
        $this->assertEquals($user->getAuthIdentifier(), $publicKey->getUser()->getId());
        $this->assertEquals($user->email, $publicKey->getUser()->getDisplayName());
    }

    /**
     * @test
     */
    public function test_do_register_data()
    {
        $user = $this->signIn();

        $publicKey = $this->app[PrepareCreationData::class]($user);
        $this->assertInstanceOf(\Webauthn\PublicKeyCredentialCreationOptions::class, $publicKey);

        $data = $this->getAttestationData($publicKey);

        $this->app[ValidateKeyCreation::class]($user, $data, 'name');

        $this->assertDatabaseHas('webauthn_keys', [
            'user_id' => $user->getAuthIdentifier(),
            'name' => 'name',
            'credentialId' => 'MA==',
            'type' => 'public-key',
            'transports' => '[]',
            'attestationType' => 'none',
            'trustPath' => '{"type":"Webauthn\\\\TrustPath\\\\EmptyTrustPath"}',
            'aaguid' => '00000000-0000-0000-0000-000000000000',
            'credentialPublicKey' => 'oWNrZXlldmFsdWU=',
            'counter' => '1',
        ]);
    }

    /**
     * @test
     */
    public function test_get_authenticate_data()
    {
        $user = $this->signIn();
        factory(WebauthnKey::class)->create([
            'user_id' => $user->getAuthIdentifier(),
        ]);

        $publicKey = $this->app[PrepareAssertionData::class]($user);

        $this->assertInstanceOf(\Webauthn\PublicKeyCredentialRequestOptions::class, $publicKey);

        $this->assertNotNull($publicKey->getChallenge());
        $this->assertEquals(32, strlen($publicKey->getChallenge()));

        $this->assertEquals('preferred', $publicKey->getUserVerification());
        $this->assertEquals('localhost', $publicKey->getRpId());
        $this->assertEquals(60000, $publicKey->getTimeout());
        $this->assertCount(0, $publicKey->getExtensions());
    }

    /**
     * @test
     */
    public function test_wrong_do_authenticate()
    {
        $user = $this->signIn();
        factory(WebauthnKey::class)->create([
            'user_id' => $user->getAuthIdentifier(),
            'credentialId' => '0',
        ]);

        $publicKey = $this->app[PrepareAssertionData::class]($user);
        $this->assertInstanceOf(\Webauthn\PublicKeyCredentialRequestOptions::class, $publicKey);

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
                    'challenge' => Base64UrlSafe::encodeUnpadded($publicKey->getChallenge()),
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
                                    new TextStringObject('key'),
                                    new TextStringObject('value')
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

    /**
     * @test
     */
    public function test_force_authenticate()
    {
        $this->assertFalse($this->app[Webauthn::class]->check());

        $this->app[Webauthn::class]->login(null);

        $this->assertTrue($this->app[Webauthn::class]->check());
    }

    /**
     * @test
     */
    public function test_enabled()
    {
        $user = $this->signIn();

        $this->assertFalse($this->app[Webauthn::class]->enabled($user));

        factory(WebauthnKey::class)->create([
            'user_id' => $user->getAuthIdentifier(),
        ]);

        $this->assertTrue($this->app[Webauthn::class]->enabled($user));
    }

    /**
     * @test
     */
    public function test_aaguid_null()
    {
        $webauthnKey = new WebauthnKey();
        $webauthnKey->aaguid = null;

        $this->assertNull($webauthnKey->getAttributeValue('aaguid'));
        $this->assertNull($webauthnKey->aaguid);
    }

    /**
     * @test
     */
    public function test_aaguid_empty()
    {
        $webauthnKey = new WebauthnKey();
        $webauthnKey->aaguid = '';

        $this->assertEquals('', $webauthnKey->getAttributeValue('aaguid'));
        $this->assertEquals('', $webauthnKey->aaguid);
    }

    /**
     * @test
     */
    public function test_aaguid_string()
    {
        $webauthnKey = new WebauthnKey();
        $webauthnKey->aaguid = '38195f59-0e5b-4ebf-be46-75664177eeee';

        $this->assertEquals('38195f59-0e5b-4ebf-be46-75664177eeee', $webauthnKey->getAttributeValue('aaguid'));
        $this->assertInstanceOf(\Symfony\Component\Uid\AbstractUid::class, $webauthnKey->aaguid);
        $this->assertEquals(Uuid::fromString('38195f59-0e5b-4ebf-be46-75664177eeee'), $webauthnKey->aaguid);
    }

    /**
     * @test
     */
    public function test_aaguid_Uuid()
    {
        $webauthnKey = new WebauthnKey();
        $webauthnKey->aaguid = Uuid::fromString('38195f59-0e5b-4ebf-be46-75664177eeee');

        $this->assertEquals('38195f59-0e5b-4ebf-be46-75664177eeee', $webauthnKey->getAttributeValue('aaguid'));
        $this->assertInstanceOf(\Symfony\Component\Uid\AbstractUid::class, $webauthnKey->aaguid);
        $this->assertEquals(Uuid::fromString('38195f59-0e5b-4ebf-be46-75664177eeee'), $webauthnKey->aaguid);
    }

    /**
     * @test
     */
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
            new NilUuid(),
            'credentialPublicKey',
            $user->id,
            0
        );

        $webauthnkey = Webauthn::create($user, 'name', $source);

        $this->assertInstanceOf(WebauthnKey::class, $webauthnkey);
    }

    /**
     * @test
     */
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
            new NilUuid(),
            'credentialPublicKey',
            $user->id,
            0
        );

        $this->expectException(ModelNotFoundException::class);
        Webauthn::create($user, 'name', $source);
    }
}

class WebauthnKeyTest extends WebauthnKey
{
}
