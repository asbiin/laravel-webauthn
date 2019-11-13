<?php

namespace LaravelWebauthn\Tests\Unit;

use Base64Url\Base64Url;
use CBOR\ListObject;
use CBOR\MapItem;
use CBOR\MapObject;
use CBOR\TextStringObject;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use LaravelWebauthn\Models\WebauthnKey;
use LaravelWebauthn\Services\Webauthn;
use LaravelWebauthn\Tests\FeatureTestCase;
use Ramsey\Uuid\Uuid;

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
        $this->assertEquals($user->getAuthIdentifier(), $publicKey->getUser()->getId());
        $this->assertEquals('john@doe.com', $publicKey->getUser()->getDisplayName());
    }

    public function test_do_register_data()
    {
        $user = $this->signIn();

        $publicKey = $this->app->make(Webauthn::class)->getRegisterData($user);
        $this->assertInstanceOf(\Webauthn\PublicKeyCredentialCreationOptions::class, $publicKey);

        $data = $this->getAttestationData($publicKey);

        $this->app->make(Webauthn::class)->doRegister($user, $publicKey, json_encode($data), 'name');

        $this->assertDatabaseHas('webauthn_keys', [
            'user_id' => $user->getAuthIdentifier(),
            'name' => 'name',
            'credentialId' => 'MA==',
            'type' => 'public-key',
            'transports' => '[]',
            'attestationType' => 'none',
            'trustPath' => '{"type":"Webauthn\\\\TrustPath\\\\EmptyTrustPath"}',
            'aaguid' => '30303030-3030-3030-3030-303030303030',
            'credentialPublicKey' => 'oWNrZXlldmFsdWU=',
            'counter' => '1',
        ]);
    }

    public function test_get_authenticate_data()
    {
        $user = $this->signIn();
        factory(WebauthnKey::class)->create([
            'user_id' => $user->getAuthIdentifier(),
        ]);

        $publicKey = $this->app->make(Webauthn::class)->getAuthenticateData($user);

        $this->assertInstanceOf(\Webauthn\PublicKeyCredentialRequestOptions::class, $publicKey);

        $this->assertNotNull($publicKey->getChallenge());
        $this->assertEquals(32, strlen($publicKey->getChallenge()));

        $this->assertEquals('preferred', $publicKey->getUserVerification());
        $this->assertEquals('localhost', $publicKey->getRpId());
        $this->assertEquals(60000, $publicKey->getTimeout());
        $this->assertCount(0, $publicKey->getExtensions());
    }

    public function test_do_authenticate()
    {
        $user = $this->signIn();
        $webauthnKey = factory(WebauthnKey::class)->create([
            'user_id' => $user->getAuthIdentifier(),
            'credentialPublicKey' => (string) new MapObject([
                new MapItem(
                    new TextStringObject('1'),
                    new TextStringObject('0')
                ),
                new MapItem(
                    new TextStringObject('3'),
                    new TextStringObject('-7')
                ),
            ]),
        ]);

        $publicKey = $this->app->make(Webauthn::class)->getAuthenticateData($user);
        $this->assertInstanceOf(\Webauthn\PublicKeyCredentialRequestOptions::class, $publicKey);

        $data = [
            'id' => Base64Url::encode($webauthnKey->credentialId),
            'rawId' => Base64Url::encode($webauthnKey->credentialId),
            'type' => 'public-key',
            'response' => [
                'clientDataJSON' => Base64Url::encode(json_encode([
                    'type' => 'webauthn.get',
                    'challenge' => Base64Url::encode($publicKey->getChallenge()),
                    'origin' => 'https://localhost',
                    'tokenBinding' => [
                        'status' => 'supported',
                        'id' => 'id',
                    ],
                ])),
                'authenticatorData' => Base64Url::encode(
                    hash('sha256', 'localhost', true). // rp_id_hash
                    pack('C', 65). // flags
                    pack('N', 1). // signCount
                    '0000000000000000'. // aaguid
                    pack('n', 1).'0'. // credentialLength
                    ((string) new MapObject([
                        new MapItem(
                            new TextStringObject('key'),
                            new TextStringObject('value')
                        ),
                    ])) // credentialPublicKey
                ),
                'signature' => Base64Url::encode(new TextStringObject('00000100000001000000010000000100000001000000010000000100000001')),
                'userHandle' => base64_encode($user->getAuthIdentifier()),
            ],
        ];

        $this->expectException(\Assert\InvalidArgumentException::class);
        $result = $this->app->make(Webauthn::class)->doAuthenticate($user, $publicKey, json_encode($data));

        $this->assertTrue($result); // Not yet ...
    }

    public function test_wrong_do_authenticate()
    {
        $user = $this->signIn();
        factory(WebauthnKey::class)->create([
            'user_id' => $user->getAuthIdentifier(),
        ]);

        $publicKey = $this->app->make(Webauthn::class)->getAuthenticateData($user);
        $this->assertInstanceOf(\Webauthn\PublicKeyCredentialRequestOptions::class, $publicKey);

        $data = $this->getAttestationData($publicKey);

        $this->expectException(\LaravelWebauthn\Exceptions\ResponseMismatchException::class);
        $result = $this->app->make(Webauthn::class)->doAuthenticate($user, $publicKey, json_encode($data));
    }

    private function getAttestationData($publicKey)
    {
        return [
            'id' => Base64Url::encode('0'),
            'rawId' => Base64Url::encode('0'),
            'type' => 'public-key',
            'response' => [
                'clientDataJSON' => Base64Url::encode(json_encode([
                    'type' => 'webauthn.create',
                    'challenge' => Base64Url::encode($publicKey->getChallenge()),
                    'origin' => 'https://localhost',
                    'tokenBinding' => [
                        'status' => 'supported',
                        'id' => 'id',
                    ],
                ])),
                'attestationObject' => Base64Url::encode((string) (new MapObject([
                    new MapItem(
                        new TextStringObject('authData'),
                        new TextStringObject(
                            hash('sha256', 'localhost', true). // rp_id_hash
                            pack('C', 65). // flags
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

    public function test_force_authenticate()
    {
        $this->assertFalse($this->app->make(Webauthn::class)->check());

        $this->app->make(Webauthn::class)->forceAuthenticate();

        $this->assertTrue($this->app->make(Webauthn::class)->check());
    }

    public function test_enabled()
    {
        $user = $this->signIn();

        $this->assertFalse($this->app->make(Webauthn::class)->enabled($user));

        factory(WebauthnKey::class)->create([
            'user_id' => $user->getAuthIdentifier(),
        ]);

        $this->assertTrue($this->app->make(Webauthn::class)->enabled($user));
    }

    public function test_aaguid_null()
    {
        $webauthnKey = new WebauthnKey();
        $webauthnKey->aaguid = null;

        $this->assertNull($webauthnKey->getAttributeValue('aaguid'));
        $this->assertNull($webauthnKey->aaguid);
    }

    public function test_aaguid_empty()
    {
        $webauthnKey = new WebauthnKey();
        $webauthnKey->aaguid = '';

        $this->assertEquals('', $webauthnKey->getAttributeValue('aaguid'));
        $this->assertEquals('', $webauthnKey->aaguid);
    }

    public function test_aaguid_string()
    {
        $webauthnKey = new WebauthnKey();
        $webauthnKey->aaguid = '38195f59-0e5b-4ebf-be46-75664177eeee';

        $this->assertEquals('38195f59-0e5b-4ebf-be46-75664177eeee', $webauthnKey->getAttributeValue('aaguid'));
        $this->assertInstanceOf(Uuid::class, $webauthnKey->aaguid);
        $this->assertEquals(Uuid::fromString('38195f59-0e5b-4ebf-be46-75664177eeee'), $webauthnKey->aaguid);
    }

    public function test_aaguid_Uuid()
    {
        $webauthnKey = new WebauthnKey();
        $webauthnKey->aaguid = Uuid::fromString('38195f59-0e5b-4ebf-be46-75664177eeee');

        $this->assertEquals('38195f59-0e5b-4ebf-be46-75664177eeee', $webauthnKey->getAttributeValue('aaguid'));
        $this->assertInstanceOf(Uuid::class, $webauthnKey->aaguid);
        $this->assertEquals(Uuid::fromString('38195f59-0e5b-4ebf-be46-75664177eeee'), $webauthnKey->aaguid);
    }
}
