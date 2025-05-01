<?php

namespace LaravelWebauthn\Tests\Unit\Actions;

use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use LaravelWebauthn\Actions\ConfirmKey;
use LaravelWebauthn\Services\Webauthn;
use LaravelWebauthn\Tests\FeatureTestCase;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class ConfirmKeyTest extends FeatureTestCase
{
    use DatabaseTransactions;

    #[Test]
    #[DataProvider('confirmKeys')]
    public function it_confirms_key(bool $result)
    {
        $guard = $this->mock(StatefulGuard::class, function (MockInterface $mock) use ($result) {
            $mock->shouldReceive('attempt')->andReturn($result);
        });
        $request = $this->mock(Request::class, function (MockInterface $mock) {
            $mock->shouldReceive('only')->andReturn([]);
        });

        $response = app(ConfirmKey::class)($guard, $request);

        $this->assertEquals($result, $response);
    }

    public static function confirmKeys(): array
    {
        return [
            [true],
            [false],
        ];
    }

    #[Test]
    public function it_confirms_key_using_callback()
    {
        $called = false;

        Webauthn::confirmKeyUsing(function ($request) use (&$called) {
            $called = true;
            $this->assertIsArray($request);

            return true;
        });

        $guard = $this->mock(StatefulGuard::class, function (MockInterface $mock) {
            $mock->shouldNotHaveBeenCalled();
        });
        $request = $this->mock(Request::class, function (MockInterface $mock) {
            $mock->shouldReceive('only')->andReturn([]);
        });

        $response = app(ConfirmKey::class)($guard, $request);

        $this->assertTrue($response);
        $this->assertTrue($called);
    }
}
