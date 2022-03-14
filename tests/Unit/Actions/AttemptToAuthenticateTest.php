<?php

namespace LaravelWebauthn\Tests\Unit\Actions;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use LaravelWebauthn\Actions\AttemptToAuthenticate;
use LaravelWebauthn\Facades\Webauthn;
use LaravelWebauthn\Services\Webauthn as WebauthnService;
use LaravelWebauthn\Tests\FeatureTestCase;

class AttemptToAuthenticateTest extends FeatureTestCase
{
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        parent::tearDown();
        WebauthnService::$authenticateUsingCallback = null;
    }

    /**
     * @test
     */
    public function it_get_user_request()
    {
        $user = $this->user();
        $request = $this->app->make(Request::class)
            ->setUserResolver(fn () => $user);

        Webauthn::shouldReceive('validateAssertion')->andReturn(true);

        $result = app(AttemptToAuthenticate::class)->handle($request, fn () => 1);

        $this->assertEquals(1, $result);
    }

    /**
     * @test
     */
    public function it_fails_with_request()
    {
        $user = $this->user();
        $request = $this->app->make(Request::class)
            ->setUserResolver(fn () => $user);

        Webauthn::shouldReceive('validateAssertion')->andReturn(false);

        $this->expectException(ValidationException::class);
        app(AttemptToAuthenticate::class)->handle($request, fn () => 1);
    }

    /**
     * @test
     */
    public function it_get_user_with_callback()
    {
        $user = $this->user();
        $request = $this->app->make(Request::class)
            ->setUserResolver(fn () => $user);

        WebauthnService::authenticateUsing(function ($r) use ($user, $request) {
            $this->assertEquals($request, $r);

            return $user;
        });
        Webauthn::shouldReceive('validateAssertion')->andReturn(true);

        $result = app(AttemptToAuthenticate::class)->handle($request, fn () => 1);

        $this->assertEquals(1, $result);
    }

    /**
     * @test
     */
    public function it_fails_with_callback()
    {
        $user = $this->user();
        $request = $this->app->make(Request::class)
            ->setUserResolver(fn () => $user);

        WebauthnService::authenticateUsing(function ($r) use ($request) {
            $this->assertEquals($request, $r);

            return null;
        });

        $this->expectException(ValidationException::class);
        app(AttemptToAuthenticate::class)->handle($request, fn () => 1);
    }
}
