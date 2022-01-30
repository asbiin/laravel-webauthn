<?php

namespace LaravelWebauthn\Tests\Unit;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use LaravelWebauthn\Actions\LoginAttempt;
use LaravelWebauthn\Actions\LoginPrepare;
use LaravelWebauthn\Actions\RegisterKeyPrepare;
use LaravelWebauthn\Actions\RegisterKeyStore;
use LaravelWebauthn\Facades\Webauthn;
use LaravelWebauthn\Models\WebauthnKey;
use LaravelWebauthn\Services\Webauthn\PublicKeyCredentialValidator;
use LaravelWebauthn\Tests\Fake\FakeWebauthn;
use LaravelWebauthn\Tests\FeatureTestCase;
use Mockery\MockInterface;

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

        Webauthn::swap(new FakeWebauthn($this->app));
    }

    public function test_auth_get()
    {
        $user = $this->signIn();

        $response = $this->get('/webauthn/auth', ['accept' => 'application/json']);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'publicKey',
        ]);
    }

    public function test_auth_success()
    {
        $user = $this->signIn();
        $this->session(['webauthn.publicKeyRequest' => app(LoginPrepare::class)($user)]);
        $this->mock(LoginAttempt::class, function (MockInterface $mock) {
            $mock->shouldReceive('__invoke')->andReturnUsing(function () {
                Webauthn::login();

                return true;
            });
        });

        $response = $this->post('/webauthn/auth', ['data' => 'x'], ['accept' => 'application/json']);

        $response->assertStatus(200);
        $response->assertJson([
            'result' => 'true',
        ]);
    }

    public function test_auth_exception()
    {
        $user = $this->signIn();

        $response = $this->post('/webauthn/auth', ['data' => 'x'], ['accept' => 'application/json']);

        $response->assertStatus(404);
    }

    public function test_auth_success_with_redirect()
    {
        config(['webauthn.redirects.login' => 'redirect']);

        $user = $this->signIn();
        $this->session(['webauthn.publicKeyRequest' => app(LoginPrepare::class)($user)]);
        $this->mock(PublicKeyCredentialValidator::class, function (MockInterface $mock) {
            $mock->shouldReceive('check')->andReturn(true);
        });

        $response = $this->post('/webauthn/auth', ['data' => 'x']);

        $response->assertStatus(302);
        $response->assertRedirect('redirect');
    }

    public function test_register_get_data()
    {
        $user = $this->signIn();

        $response = $this->get('/webauthn/keys/create', ['accept' => 'application/json']);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'publicKey' => $this->publicKeyForm,
        ]);
    }

    public function test_register_create_without_check()
    {
        $user = $this->signIn();
        $response = $this->post('/webauthn/keys', [
            'register' => 'x',
            'name' => 'keyname',
        ], ['accept' => 'application/json']);

        $response->assertStatus(404);
    }

    public function test_register_create()
    {
        $user = $this->signIn();
        $this->session(['webauthn.publicKeyCreation' => app(RegisterKeyPrepare::class)($user)]);
        $this->mock(RegisterKeyStore::class, function (MockInterface $mock) use ($user) {
            $mock->shouldReceive('__invoke')->andReturn(factory(WebauthnKey::class)->create([
                'user_id' => $user->getAuthIdentifier(),
            ]));
        });

        $response = $this->post('/webauthn/keys', [
            'register' => 'x',
            'name' => 'keyname',
        ], ['accept' => 'application/json']);

        $response->assertStatus(201);
        $response->assertJson([
            'result' => true,
        ]);

        $this->assertDataBaseHas('webauthn_keys', [
            'user_id' => $user->getAuthIdentifier(),
        ]);
    }

    public function test_register_create_exception()
    {
        $user = $this->signIn();

        $response = $this->post('/webauthn/keys', [
            'register' => '',
            'name' => 'keyname',
        ], ['accept' => 'application/json']);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'The given data was invalid.',
            'errors' => [
                'register' => [
                    'The register field is required.',
                ],
            ],
        ]);
    }

    public function test_destroy()
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

    public function test_destroy_wrong_id()
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

        $this->assertDataBaseHas('webauthn_keys', [
            'id' => $webauthnKey->id,
            'user_id' => $user->getAuthIdentifier(),
        ]);
    }

    public function test_destroy_wrong_user()
    {
        $user = $this->signIn();
        $webauthnKey = factory(WebauthnKey::class)->create([
            'user_id' => $user->getAuthIdentifier(),
        ]);
        $otherWebauthnKey = factory(WebauthnKey::class)->create([
            'user_id' => $this->user()->getAuthIdentifier(),
        ]);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $response = $this->delete('/webauthn/keys/'.$otherWebauthnKey->id, ['accept' => 'application/json']);

        $response->assertStatus(404);
        $response->assertJson([
            'error' => [
                'message' => 'Object not found',
            ],
        ]);

        $this->assertDataBaseHas('webauthn_keys', [
            'id' => $webauthnKey->id,
            'user_id' => $user->getAuthIdentifier(),
        ]);
    }
}
