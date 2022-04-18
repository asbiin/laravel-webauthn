<?php

namespace LaravelWebauthn\Tests\Unit\Http\Controllers;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use LaravelWebauthn\Actions\ValidateKeyCreation;
use LaravelWebauthn\Facades\Webauthn;
use LaravelWebauthn\Models\WebauthnKey;
use LaravelWebauthn\Tests\FeatureTestCase;
use Mockery\MockInterface;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;

class WebauthnControllerTest extends FeatureTestCase
{
    use DatabaseTransactions;

    protected $publicKeyForm = [
        'user' => [
            'name',
            'id',
            'displayName',
        ],
        'challenge',
        'attestation',
        'timeout',
        'rp' => [
            'name',
            'id',
        ],
        'pubKeyCredParams' => [
            '*' => [
                'type',
                'alg',
            ],
        ],
        'authenticatorSelection' => [
            'requireResidentKey',
            'userVerification',
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Webauthn::spy();
        Webauthn::shouldReceive('model')->andReturn(WebauthnKey::class);
    }

    /**
     * @test
     */
    public function it_register_get_data()
    {
        $user = $this->signIn();
        Webauthn::shouldReceive('canRegister')->andReturn(true);

        $rpEntity = $this->mock(PublicKeyCredentialRpEntity::class, function (MockInterface $mock) {
            $mock->shouldReceive('jsonSerialize')->andReturn(['id' => 'id']);
        });
        $userEntity = $this->mock(PublicKeyCredentialUserEntity::class, function (MockInterface $mock) {
            $mock->shouldReceive('jsonSerialize')->andReturn(['id' => 'id']);
        });
        Webauthn::shouldReceive('prepareAttestation')->andReturn(new PublicKeyCredentialCreationOptions(
            $rpEntity,
            $userEntity,
            'challenge',
            []
        ));

        $response = $this->post('/webauthn/keys/options', [], ['accept' => 'application/json']);

        $response->assertStatus(200);
        $this->assertEquals('Y2hhbGxlbmdl', $response->json('publicKey.challenge'));
    }

    /**
     * @test
     */
    public function it_register_create_without_check()
    {
        $user = $this->signIn();

        $response = $this->post('/webauthn/keys', [
            'id' => 'id',
            'type' => 'type',
            'rawId' => 'rawId',
            'response' => [
                'attestationObject' => 'attestationObject',
                'clientDataJSON' => 'clientDataJSON',
            ],
            'name' => 'keyname',
        ], ['accept' => 'application/json']);

        $response->assertStatus(422);
    }

    /**
     * @test
     */
    public function it_register_create()
    {
        $user = $this->signIn();
        $this->mock(ValidateKeyCreation::class, function (MockInterface $mock) use ($user) {
            $mock->shouldReceive('__invoke')->andReturn(factory(WebauthnKey::class)->create([
                'name' => 'keyname',
                'user_id' => $user->getAuthIdentifier(),
            ]));
        });

        $response = $this->post('/webauthn/keys', [
            'id' => 'id',
            'type' => 'type',
            'rawId' => 'rawId',
            'response' => [
                'attestationObject' => 'attestationObject',
                'clientDataJSON' => 'clientDataJSON',
            ],
            'name' => 'keyname',
        ], ['accept' => 'application/json']);

        $response->assertStatus(201);
        $response->assertJson([
            'result' => [
                'name' => 'keyname',
            ],
        ]);

        $this->assertDatabaseHas('webauthn_keys', [
            'user_id' => $user->getAuthIdentifier(),
        ]);
    }

    /**
     * @test
     */
    public function it_register_create_exception()
    {
        $user = $this->signIn();

        $response = $this->post('/webauthn/keys', [
            'name' => 'keyname',
        ], ['accept' => 'application/json']);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'id' => [
                    'The id field is required.',
                ],
                'type' => [
                    'The type field is required.',
                ],
                'rawId' => [
                    'The raw id field is required.',
                ],
                'response.attestationObject' => [
                    'The response.attestation object field is required.',
                ],
                'response.clientDataJSON' => [
                    'The response.client data j s o n field is required.',
                ],
            ],
        ]);
    }

    /**
     * @test
     */
    public function it_destroy_key()
    {
        $user = $this->signIn();
        $webauthnKey = factory(WebauthnKey::class)->create([
            'user_id' => $user->getAuthIdentifier(),
        ]);

        $response = $this->delete('/webauthn/keys/'.$webauthnKey->id, ['accept' => 'application/json']);

        $response->assertStatus(302);

        $this->assertDataBaseMissing('webauthn_keys', [
            'user_id' => $user->getAuthIdentifier(),
        ]);
    }

    /**
     * @test
     */
    public function it_destroy_wrong_id()
    {
        $user = $this->signIn();
        $webauthnKey = factory(WebauthnKey::class)->create([
            'user_id' => $user->getAuthIdentifier(),
        ]);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $response = $this->delete('/webauthn/keys/0', ['accept' => 'application/json']);

        $response->assertStatus(404);
        $response->assertJson([
            'error' => [
                'message' => 'Object not found',
            ],
        ]);

        $this->assertDatabaseHas('webauthn_keys', [
            'id' => $webauthnKey->id,
            'user_id' => $user->getAuthIdentifier(),
        ]);
    }

    /**
     * @test
     */
    public function it_destroy_wrong_user()
    {
        $user = $this->signIn();
        $webauthnKey = factory(WebauthnKey::class)->create([
            'user_id' => $user->getAuthIdentifier(),
        ]);
        $otherWebauthnKey = factory(WebauthnKey::class)->create();

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $response = $this->delete('/webauthn/keys/'.$otherWebauthnKey->id, ['accept' => 'application/json']);

        $response->assertStatus(404);
        $response->assertJson([
            'error' => [
                'message' => 'Object not found',
            ],
        ]);

        $this->assertDatabaseHas('webauthn_keys', [
            'id' => $webauthnKey->id,
            'user_id' => $user->getAuthIdentifier(),
        ]);
    }

    /**
     * @test
     */
    public function it_update_webauthnkey()
    {
        $user = $this->signIn();
        $webauthnKey = factory(WebauthnKey::class)->create([
            'user_id' => $user->getAuthIdentifier(),
        ]);

        $response = $this->put('/webauthn/keys/'.$webauthnKey->id, [
            'name' => 'new name',
        ], ['accept' => 'application/json']);

        $response->assertStatus(204);

        $this->assertDataBaseHas('webauthn_keys', [
            'user_id' => $user->getAuthIdentifier(),
            'name' => 'new name',
        ]);
    }

    /**
     * @test
     */
    public function it_not_update_wrong_id()
    {
        $user = $this->signIn();
        $webauthnKey = factory(WebauthnKey::class)->create([
            'user_id' => $user->getAuthIdentifier(),
            'name' => 'name',
        ]);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $response = $this->put('/webauthn/keys/0', [
            'name' => 'new name',
        ]);

        $response->assertStatus(404);
        $response->assertJson([
            'error' => [
                'message' => 'Object not found',
            ],
        ]);

        $this->assertDatabaseHas('webauthn_keys', [
            'id' => $webauthnKey->id,
            'user_id' => $user->getAuthIdentifier(),
            'name' => 'name',
        ]);
    }

    /**
     * @test
     */
    public function it_not_update_wrong_user()
    {
        $user = $this->signIn();
        $webauthnKey = factory(WebauthnKey::class)->create([
            'user_id' => $user->getAuthIdentifier(),
            'name' => 'name',
        ]);
        $otherWebauthnKey = factory(WebauthnKey::class)->create([
            'user_id' => $this->user()->getAuthIdentifier(),
        ]);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $response = $this->put('/webauthn/keys/'.$otherWebauthnKey->id, [
            'name' => 'new name',
        ]);

        $response->assertStatus(404);
        $response->assertJson([
            'error' => [
                'message' => 'Object not found',
            ],
        ]);

        $this->assertDatabaseHas('webauthn_keys', [
            'id' => $webauthnKey->id,
            'user_id' => $user->getAuthIdentifier(),
            'name' => 'name',
        ]);
    }
}
