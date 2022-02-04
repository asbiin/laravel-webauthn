<?php

namespace LaravelWebauthn\Tests\Unit\Actions;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use LaravelWebauthn\Actions\LoginAttempt;
use LaravelWebauthn\FAcades\Webauthn;
use LaravelWebauthn\Services\Webauthn\RequestOptionsFactory;
use LaravelWebauthn\Services\Webauthn\CredentialAssertionValidator;
use LaravelWebauthn\Tests\FeatureTestCase;
use Mockery\MockInterface;

class LoginAttemptTest extends FeatureTestCase
{
    use DatabaseTransactions;

    /**
     * @test
     */
    public function it_login()
    {
        $user = $this->user();

        $publicKeyCredentialRequestOptions = $this->app[RequestOptionsFactory::class]($user);

        $this->mock(CredentialAssertionValidator::class, function (MockInterface $mock) use ($user, $publicKeyCredentialRequestOptions) {
            $mock->shouldReceive('__invoke')
                ->with($user, $publicKeyCredentialRequestOptions, 'x')
                ->andReturn(true);
        });

        $this->app[LoginAttempt::class]($user, $publicKeyCredentialRequestOptions, 'x');

        $this->assertTrue(Webauthn::check());
    }

    /**
     * @test
     */
    public function it_does_not_login()
    {
        $user = $this->user();

        $publicKeyCredentialRequestOptions = $this->app[RequestOptionsFactory::class]($user);

        $this->mock(CredentialAssertionValidator::class, function (MockInterface $mock) use ($user, $publicKeyCredentialRequestOptions) {
            $mock->shouldReceive('__invoke')
                ->with($user, $publicKeyCredentialRequestOptions, 'x')
                ->andReturn(false);
        });

        $this->app[LoginAttempt::class]($user, $publicKeyCredentialRequestOptions, 'x');

        $this->assertFalse(Webauthn::check());
    }

    /**
     * @test
     */
    public function it_fails_login()
    {
        $user = $this->user();

        $publicKeyCredentialRequestOptions = $this->app[RequestOptionsFactory::class]($user);

        $this->mock(CredentialAssertionValidator::class, function (MockInterface $mock) use ($user, $publicKeyCredentialRequestOptions) {
            $mock->shouldReceive('__invoke')
                ->with($user, $publicKeyCredentialRequestOptions, 'x')
                ->andThrow(new \Exception());
        });

        $this->expectException(ValidationException::class);
        $this->app[LoginAttempt::class]($user, $publicKeyCredentialRequestOptions, 'x');

        $this->assertFalse(Webauthn::check());
    }
}
