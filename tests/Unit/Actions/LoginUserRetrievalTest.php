<?php

namespace LaravelWebauthn\Tests\Unit\Actions;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use LaravelWebauthn\Actions\LoginUserRetrieval;
use LaravelWebauthn\Tests\FeatureTestCase;
use PHPUnit\Framework\Attributes\Test;

class LoginUserRetrievalTest extends FeatureTestCase
{
    use DatabaseTransactions;

    #[Test]
    public function it_get_user_request()
    {
        $user = $this->user();
        $request = $this->app->make(Request::class)
            ->setUserResolver(fn () => $user);

        $result = app(LoginUserRetrieval::class)($request);

        $this->assertEquals($user, $result);
    }

    #[Test]
    public function it_get_user_fail()
    {
        $request = $this->app->make(Request::class);

        $this->expectException(ValidationException::class);
        app(LoginUserRetrieval::class)($request);
    }
}
