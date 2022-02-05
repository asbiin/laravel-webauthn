<?php

namespace LaravelWebauthn\Http\Helpers;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Log;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @psalm-suppress UndefinedClass
 */
class PsrHelper
{
    /**
     * Get the PSR-18 Client.
     *
     * @return \Psr\Http\Client\ClientInterface
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public static function getClient(): ClientInterface
    {
        if (class_exists(\Http\Discovery\Psr18ClientDiscovery::class)
            && class_exists(\Http\Discovery\Exception\NotFoundException::class)) {
            try {
                return \Http\Discovery\Psr18ClientDiscovery::find();
            } catch (\Http\Discovery\Exception\NotFoundException $e) {
                Log::error('Could not find PSR-18 Client Factory.', ['exception' => $e]);
                throw new BindingResolutionException('Unable to resolve PSR-18 Client Factory. Please install a psr/http-client-implementation implementation like \'guzzlehttp/guzzle\'.');
            }
        }

        throw new BindingResolutionException('Unable to resolve PSR-18 request. Please install php-http/discovery and implementations for psr/http-client-implementation.');
    }

    /**
     * Get the PSR-17 Request Factory.
     *
     * @return \Psr\Http\Message\RequestFactoryInterface
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public static function getRequestFactory(): RequestFactoryInterface
    {
        if (class_exists(\Http\Discovery\Psr17FactoryDiscovery::class)
            && class_exists(\Http\Discovery\Exception\NotFoundException::class)) {
            try {
                return \Http\Discovery\Psr17FactoryDiscovery::findRequestFactory();
            } catch (\Http\Discovery\Exception\NotFoundException $e) {
                Log::error('Could not find PSR-17 Request Factory.', ['exception' => $e]);
                throw new BindingResolutionException('Unable to resolve PSR-17 Request Factory. Please install psr/http-factory-implementation implementation like \'guzzlehttp/psr7\'.');
            }
        }

        throw new BindingResolutionException('Unable to resolve PSR-17 request. Please install php-http/discovery and implementations for psr/http-factory-implementation.');
    }

    /**
     * Get the PSR-7 Server Request.
     *
     * @return \Psr\Http\Message\ServerRequestInterface
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public static function getServerRequestInterface(): ServerRequestInterface
    {
        if (class_exists(\Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory::class)) {
            if (class_exists(\Nyholm\Psr7\Factory\Psr17Factory::class)) {
                return app(ServerRequestInterface::class);
            } elseif (class_exists(\Http\Discovery\Psr17FactoryDiscovery::class)
                && class_exists(\Http\Discovery\Exception\NotFoundException::class)) {
                try {
                    $uploadFileFactory = \Http\Discovery\Psr17FactoryDiscovery::findUploadedFileFactory();
                    $responseFactory = \Http\Discovery\Psr17FactoryDiscovery::findResponseFactory();
                    $serverRequestFactory = \Http\Discovery\Psr17FactoryDiscovery::findServerRequestFactory();
                    $streamFactory = \Http\Discovery\Psr17FactoryDiscovery::findStreamFactory();

                    return (new \Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory($serverRequestFactory, $streamFactory, $uploadFileFactory, $responseFactory))
                            ->createRequest(request());
                } catch (\Http\Discovery\Exception\NotFoundException $e) {
                    Log::error('Could not find PSR-17 Factory.', ['exception' => $e]);
                    throw new BindingResolutionException('Unable to resolve PSR-17 Factory. Please install psr/http-factory-implementation implementation like \'guzzlehttp/psr7\'.');
                }
            }
        } elseif (class_exists(\GuzzleHttp\Psr7\ServerRequest::class)) {
            return \GuzzleHttp\Psr7\ServerRequest::fromGlobals();
        }

        throw new BindingResolutionException('Unable to resolve PSR-7 Server Request. Please install the guzzlehttp/psr7 or symfony/psr-http-message-bridge, php-http/discovery and a psr/http-factory-implementation implementation.');
    }
}
