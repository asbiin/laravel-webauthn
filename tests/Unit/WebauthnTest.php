<?php

namespace LaravelWebauthn\Tests\Unit;

use CBOR\MapItem;
use CBOR\MapObject;
use CBOR\ListObject;
use Base64Url\Base64Url;
use CBOR\TextStringObject;
use Webauthn\CollectedClientData;
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

        $data = [
            'id' => Base64Url::encode('0'),
            'rawId' => Base64Url::encode('0'),
            'type' => 'public-key',
            'response' => [
                'clientDataJSON' => Base64Url::encode(json_encode([
                    'type' => 'webauthn.create',
                    'challenge' => Base64Url::encode($publicKey->getChallenge()),
                    'origin' => 'https://localhost',
                    'tokenBinding' => ['status' => 'supported', 'id' => 'id'],
                ])),
                'attestationObject' => Base64Url::encode((string) (new MapObject([
                    new MapItem(
                        new TextStringObject('authData'),
                        new TextStringObject(
                            hash('sha256', 'localhost', true).
                            pack('C', 65).
                            pack('N', 1).'0'.
                            '000000000000000'.
                            pack('n', 1).'0'.
                            ((string)new MapObject([]))
                        )
                    ),
                    new MapItem(new TextStringObject('fmt'), new TextStringObject('none')),
                    new MapItem(new TextStringObject('attStmt'), new ListObject([])),
                ]))),
            ],
        ];

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
            'credentialPublicKey' => 'oA==',
            'userHandle' => '0',
            'counter' => '1',    
        ]);
    }
}
