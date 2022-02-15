<?php

namespace LaravelWebauthn\Tests\Unit\Http\Controllers;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use LaravelWebauthn\Actions\AttemptToAuthenticate;
use LaravelWebauthn\Facades\Webauthn;
use LaravelWebauthn\Tests\FeatureTestCase;
use Mockery\MockInterface;
use Webauthn\PublicKeyCredentialRequestOptions;

class AuthenticateControllerTest extends FeatureTestCase
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
    }

    /**
     * @test
     */
    public function it_auth_get()
    {
        $user = $this->signIn();

        Webauthn::shouldReceive('canRegister')->andReturn(true);

        $publicKey = $this->mock(PublicKeyCredentialRequestOptions::class, function (MockInterface $mock) {
            $mock->shouldReceive('jsonSerialize')->andReturn(['key']);
        });
        Webauthn::shouldReceive('prepareAssertion')->andReturn($publicKey);

        $response = $this->get('/webauthn/auth', ['accept' => 'application/json']);

        $response->assertStatus(200);
        $this->assertEquals(['key'], $response->json('publicKey'));
    }

    /**
     * @test
     */
    public function it_auth_success()
    {
        $user = $this->signIn();
        $this->mock(AttemptToAuthenticate::class, function (MockInterface $mock) {
            $mock->shouldReceive('handle')->andReturnUsing(function ($request, $next) {
                $next($request);
            });
        });

        $response = $this->post('/webauthn/auth', [
            'id' => 'id',
            'type' => 'type',
            'rawId' => 'rawId',
            'response' => [
                'authenticatorData' => 'authenticatorData',
                'clientDataJSON' => 'clientDataJSON',
                'signature' => 'signature',
                'userHandle' => 'userHandle',
            ],
            'remember' => true,
        ], ['accept' => 'application/json']);

        $response->assertStatus(200);
    }

    /**
     * @test
     */
    public function it_auth_success2()
    {
        $user = $this->signIn();

        Webauthn::shouldReceive('validateAssertion')->andReturn(true);

        $response = $this->post('/webauthn/auth', [
            'id' => 'id',
            'type' => 'type',
            'rawId' => 'rawId',
            'response' => [
                'authenticatorData' => 'authenticatorData',
                'clientDataJSON' => 'clientDataJSON',
                'signature' => 'signature',
                'userHandle' => 'userHandle',
            ],
            'remember' => true,
        ], ['accept' => 'application/json']);

        $response->assertStatus(200);
    }

    /**
     * @test
     */
    public function it_auth_exception()
    {
        $user = $this->signIn();

        Webauthn::shouldReceive('validateAssertion')->andReturn(false);

        $response = $this->post('/webauthn/auth', [
            'id' => 'id',
            'type' => 'type',
            'rawId' => 'rawId',
            'response' => [
                'authenticatorData' => 'authenticatorData',
                'clientDataJSON' => 'clientDataJSON',
                'signature' => 'signature',
                'userHandle' => 'userHandle',
            ],
            'remember' => true,
        ], ['accept' => 'application/json']);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'The given data was invalid.',
            'errors' => [
                'email' => [
                    'Authentication failed',
                ],
            ],
        ]);
    }

    /**
     * @test
     */
    public function it_auth_success_with_redirect()
    {
        $user = $this->signIn();

        Webauthn::shouldReceive('redirects')->andReturn('redirect');
        Webauthn::shouldReceive('validateAssertion')->andReturn(true);

        $response = $this->post('/webauthn/auth', [
            'id' => 'id',
            'type' => 'type',
            'rawId' => 'rawId',
            'response' => [
                'authenticatorData' => 'authenticatorData',
                'clientDataJSON' => 'clientDataJSON',
                'signature' => 'signature',
                'userHandle' => 'userHandle',
            ],
            'remember' => true,
        ]);

        $response->assertStatus(302);
        $response->assertRedirect('redirect');
    }
}
