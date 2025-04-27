<?php

namespace LaravelWebauthn\Tests\Unit\Services;

use LaravelWebauthn\Tests\FeatureTestCase;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;

class PsrHelpersTest extends FeatureTestCase
{
    #[Test]
    public function it_get_client()
    {
        if (! class_exists(\Http\Discovery\Psr17FactoryDiscovery::class)) {
            $this->markTestSkipped('PSR-17 Request Factory not found.');

            return;
        }

        $client = app(ClientInterface::class);

        $this->assertInstanceOf(ClientInterface::class, $client);
    }

    #[Test]
    public function it_get_request_factory()
    {
        if (! class_exists(\Http\Discovery\Psr17FactoryDiscovery::class)) {
            $this->markTestSkipped('PSR-17 Request Factory not found.');

            return;
        }

        $requestFactory = app(RequestFactoryInterface::class);

        $this->assertInstanceOf(RequestFactoryInterface::class, $requestFactory);
    }

    #[Test]
    public function it_get_server_request_interface()
    {
        $serverRequest = app(ServerRequestInterface::class);

        $this->assertInstanceOf(ServerRequestInterface::class, $serverRequest);
    }
}
