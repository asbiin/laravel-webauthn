<?php

namespace LaravelWebauthn\Tests\Unit;

use CBOR\MapItem;
use CBOR\MapObject;
use CBOR\ListObject;
use Base64Url\Base64Url;
use CBOR\TextStringObject;
use LaravelWebauthn\Services\Webauthn;
use LaravelWebauthn\Models\WebauthnKey;
use Webauthn\TokenBinding\TokenBinding;
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

        $publicKey = $this->app->make(Webauthn::class)->getRegisterData($user);
        $this->assertInstanceOf(\Webauthn\PublicKeyCredentialCreationOptions::class, $publicKey);

        $data = $this->getAttestationData($publicKey);

        $this->app->make(Webauthn::class)->doRegister($user, $publicKey, json_encode($data), 'name');

        $this->assertDatabaseHas('webauthn_keys', [
            'user_id' => 0,
            'name' => 'name',
            'credentialId' => 'MA==',
            'type' => 'public-key',
            'transports' => '[]',
            'attestationType' => 'none',
            'trustPath' => '{"type":"empty"}',
            'aaguid' => '0000000000000000',
            'credentialPublicKey' => 'oWNrZXlldmFsdWU=',
            'userHandle' => '0',
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

    /*
    public function test_do_authenticate()
    {
        $user = $this->signIn();
        $webauthnKey = factory(WebauthnKey::class)->create([
            'user_id' => $user->getAuthIdentifier(),
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
                        'id' => 'id'
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
                'signature' => Base64Url::encode(new TextStringObject('')),
                'userHandle' => base64_encode($user->getAuthIdentifier()),
            ],
        ];

        $result = $this->app->make(Webauthn::class)->doAuthenticate($user, $publicKey, json_encode($data));

        $this->assertTrue($result);
    }
    */

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
}
