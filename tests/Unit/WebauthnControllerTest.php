<?php

namespace LaravelWebauthn\Tests\Unit;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use LaravelWebauthn\Facades\Webauthn;
use LaravelWebauthn\Models\WebauthnKey;
use LaravelWebauthn\Tests\Fake\FakeWebauthn;
use LaravelWebauthn\Tests\FeatureTestCase;

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
        config(['webauthn.authenticate.view' => '']);

        $user = $this->signIn();

        $response = $this->get('/webauthn/auth');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'publicKey',
        ]);
    }

    public function test_auth_success()
    {
        config(['webauthn.authenticate.postSuccessCallback' => false]);

        $user = $this->signIn();
        $this->session(['webauthn.publicKeyRequest' => Webauthn::getAuthenticateData($user)]);

        $response = $this->post('/webauthn/auth', ['data' => '']);

        $response->assertStatus(200);
        $response->assertJson([
            'result' => 'true',
        ]);
    }

    public function test_auth_exception()
    {
        $user = $this->signIn();

        $response = $this->post('/webauthn/auth', ['data' => '']);

        $response->assertStatus(403);
        $response->assertJson([
            'error' => [
                'message' => 'Authentication data not found',
            ],
        ]);
    }

    public function test_auth_success_with_redirect()
    {
        config(['webauthn.authenticate.postSuccessCallback' => false]);
        config(['webauthn.authenticate.postSuccessRedirectRoute' => 'redirect']);

        $user = $this->signIn();
        $this->session(['webauthn.publicKeyRequest' => Webauthn::getAuthenticateData($user)]);

        $response = $this->post('/webauthn/auth', ['data' => '']);

        $response->assertStatus(302);
        $response->assertRedirect('redirect');
    }

    public function test_register_get_data()
    {
        config(['webauthn.register.view' => '']);

        $user = $this->signIn();

        $response = $this->get('/webauthn/register');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'publicKey' => $this->publicKeyForm,
        ]);
    }

    public function test_register_create()
    {
        config(['webauthn.register.postSuccessRedirectRoute' => '']);

        $user = $this->signIn();
        $this->session(['webauthn.publicKeyCreation' => Webauthn::getRegisterData($user)]);

        $response = $this->post('/webauthn/register', [
            'register' => '',
            'name' => 'keyname',
        ]);

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

        $response = $this->post('/webauthn/register', [
            'register' => '',
            'name' => 'keyname',
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'error' => [
                'message' => 'Register data not found',
            ],
        ]);
    }

    public function test_destroy()
    {
        $user = $this->signIn();
        $webauthnKey = factory(WebauthnKey::class)->create([
            'user_id' => $user->getAuthIdentifier(),
        ]);

        $response = $this->delete('/webauthn/'.$webauthnKey->id);

        $response->assertStatus(200);
        $response->assertJson([
            'deleted' => true,
            'id' => $webauthnKey->id,
        ]);

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

        $response = $this->delete('/webauthn/0');

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

        $response = $this->delete('/webauthn/'.$otherWebauthnKey->id);

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
