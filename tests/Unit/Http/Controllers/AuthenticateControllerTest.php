<?php

namespace LaravelWebauthn\Tests\Unit\Http\Controllers;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use LaravelWebauthn\Actions\AttemptToAuthenticate;
use LaravelWebauthn\Facades\Webauthn;
use LaravelWebauthn\Services\Webauthn\PublicKeyCredentialRequestOptions;
use LaravelWebauthn\Tests\FeatureTestCase;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Webauthn\PublicKeyCredentialRequestOptions as PublicKeyCredentialRequestOptionsBase;

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

    #[Test]
    public function it_auth_get()
    {
        $this->signIn();

        Webauthn::shouldReceive('canRegister')->andReturn(true);

        Webauthn::shouldReceive('prepareAssertion')->andReturn(PublicKeyCredentialRequestOptions::create(new PublicKeyCredentialRequestOptionsBase('challenge')));

        $response = $this->get('/webauthn/auth', ['accept' => 'application/json']);

        $response->assertStatus(200);
        $this->assertEquals('Y2hhbGxlbmdl', $response->json('publicKey.challenge'));
    }

    #[Test]
    public function it_auth_success()
    {
        $this->signIn();
        $this->mock(AttemptToAuthenticate::class, function (MockInterface $mock) {
            $mock->shouldReceive('handle')->andReturnUsing(fn (Request $request, \Closure $next): mixed => $next($request));
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
            'remember' => 'on',
        ], ['accept' => 'application/json']);

        $response->assertStatus(200);
    }

    #[Test]
    public function it_auth_success2()
    {
        $this->signIn();

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
            'remember' => 'on',
        ], ['accept' => 'application/json']);

        $response->assertStatus(200);
    }

    #[Test]
    public function it_auth_exception()
    {
        $this->signIn();

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
            'remember' => 'on',
        ], ['accept' => 'application/json']);

        $response->assertStatus(422);
        $response->assertJson([
            'errors' => [
                'email' => [
                    'Authentication failed',
                ],
            ],
        ]);
    }

    #[Test]
    public function it_auth_success_with_redirect()
    {
        $this->signIn();

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
            'remember' => 'on',
        ]);

        $response->assertStatus(302);
        $response->assertRedirect('redirect');
    }
}
