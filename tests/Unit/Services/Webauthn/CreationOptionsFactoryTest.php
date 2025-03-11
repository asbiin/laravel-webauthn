<?php

namespace LaravelWebauthn\Tests\Unit\Services\Webauthn;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use LaravelWebauthn\Services\Webauthn\RequestOptionsFactory;
use LaravelWebauthn\Tests\FeatureTestCase;
use PHPUnit\Framework\Attributes\Test;
use Webauthn\PublicKeyCredentialRequestOptions;

class CreationOptionsFactoryTest extends FeatureTestCase
{
    use DatabaseTransactions;

    #[Test]
    public function create_options_factory()
    {
        $user = $this->user();

        $option = app(RequestOptionsFactory::class)($user);

        $this->assertInstanceOf(PublicKeyCredentialRequestOptions::class, $option->data);
        $this->assertNotNull($option->data->challenge);
        $this->assertEquals(32, strlen($option->data->challenge));
        $this->assertNull($option->data->timeout);
    }

    #[Test]
    public function create_options_factory_timeout()
    {
        config(['webauthn.timeout' => 300000]);

        $user = $this->user();

        $this->mock(Cache::class, function ($mock) {
            $mock->shouldReceive('put')
                ->withArgs(function ($key, $value, $timeout) {
                    $this->assertEquals(300, $timeout);

                    return true;
                })
                ->andReturnTrue();
        });

        $option = app(RequestOptionsFactory::class)($user);

        $this->assertInstanceOf(PublicKeyCredentialRequestOptions::class, $option->data);
        $this->assertNotNull($option->data->challenge);
        $this->assertEquals(32, strlen($option->data->challenge));
        $this->assertEquals(300000, $option->data->timeout);
    }

    #[Test]
    public function create_options_factory_discouraged()
    {
        config(['webauthn.user_verification' => 'discouraged']);

        $user = $this->user();

        $this->mock(Cache::class, function ($mock) {
            $mock->shouldReceive('put')
                ->withArgs(function ($key, $value, $timeout) {
                    $this->assertEquals(180, $timeout);

                    return true;
                })
                ->andReturnTrue();
        });

        $option = app(RequestOptionsFactory::class)($user);

        $this->assertInstanceOf(PublicKeyCredentialRequestOptions::class, $option->data);
        $this->assertNotNull($option->data->challenge);
        $this->assertEquals(32, strlen($option->data->challenge));
        $this->assertNull($option->data->timeout);
    }

    #[Test]
    public function create_options_factory_required()
    {
        config(['webauthn.user_verification' => 'required']);

        $user = $this->user();

        $this->mock(Cache::class, function ($mock) {
            $mock->shouldReceive('put')
                ->withArgs(function ($key, $value, $timeout) {
                    $this->assertEquals(600, $timeout);

                    return true;
                })
                ->andReturnTrue();
        });

        $option = app(RequestOptionsFactory::class)($user);

        $this->assertInstanceOf(PublicKeyCredentialRequestOptions::class, $option->data);
        $this->assertNotNull($option->data->challenge);
        $this->assertEquals(32, strlen($option->data->challenge));
        $this->assertNull($option->data->timeout);
    }

    #[Test]
    public function create_options_factory_preferred()
    {
        config(['webauthn.user_verification' => 'preferred']);

        $user = $this->user();

        $this->mock(Cache::class, function ($mock) {
            $mock->shouldReceive('put')
                ->withArgs(function ($key, $value, $timeout) {
                    $this->assertEquals(600, $timeout);

                    return true;
                })
                ->andReturnTrue();
        });

        $option = app(RequestOptionsFactory::class)($user);

        $this->assertInstanceOf(PublicKeyCredentialRequestOptions::class, $option->data);
        $this->assertNotNull($option->data->challenge);
        $this->assertEquals(32, strlen($option->data->challenge));
        $this->assertNull($option->data->timeout);
    }
}
