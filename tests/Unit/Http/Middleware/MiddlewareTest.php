<?php

namespace LaravelWebauthn\Tests\Unit\Http\Middleware;

use Illuminate\Http\Request;
use LaravelWebauthn\Http\Middleware\WebauthnMiddleware;
use LaravelWebauthn\Models\WebauthnKey;
use LaravelWebauthn\Services\Webauthn;
use LaravelWebauthn\Tests\FeatureTestCase;

class MiddlewareTest extends FeatureTestCase
{
    public function test_middleware_guest()
    {
        $request = new Request();

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->app[WebauthnMiddleware::class]->handle($request, fn () => null);
    }

    public function test_middleware_user_not_enabled()
    {
        $user = $this->signIn();
        $request = $this->getRequest($user);

        $result = $this->app[WebauthnMiddleware::class]->handle($request, fn () => 'next');

        $this->assertEquals('next', $result);
    }

    public function test_middleware_user_authenticated()
    {
        $user = $this->signIn();
        $request = $this->getRequest($user);

        $this->app[Webauthn::class]->login($user);

        $result = $this->app[WebauthnMiddleware::class]->handle($request, fn () => 'next');

        $this->assertEquals('next', $result);
    }

    public function test_middleware_user_enabled()
    {
        $user = $this->signIn();
        factory(WebauthnKey::class)->create([
            'user_id' => $user->getAuthIdentifier(),
        ]);

        $request = $this->getRequest($user);

        $result = $this->app[WebauthnMiddleware::class]->handle($request, fn () => 'next');

        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $result);
    }

    private function getRequest($user)
    {
        return (new Request())
            ->setUserResolver(fn () => $user);
    }
}
