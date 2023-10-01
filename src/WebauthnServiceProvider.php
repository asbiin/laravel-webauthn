<?php

namespace LaravelWebauthn;

use Cose\Algorithm\Manager as CoseAlgorithmManager;
use Cose\Algorithm\ManagerFactory as CoseAlgorithmManagerFactory;
use Cose\Algorithm\Signature\ECDSA;
use Cose\Algorithm\Signature\EdDSA;
use Cose\Algorithm\Signature\RSA;
use Http\Discovery\Exception\NotFoundException;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use LaravelWebauthn\Auth\EloquentWebAuthnProvider;
use LaravelWebauthn\Contracts\DestroyResponse as DestroyResponseContract;
use LaravelWebauthn\Contracts\LoginSuccessResponse as LoginSuccessResponseContract;
use LaravelWebauthn\Contracts\LoginViewResponse as LoginViewResponseContract;
use LaravelWebauthn\Contracts\RegisterSuccessResponse as RegisterSuccessResponseContract;
use LaravelWebauthn\Contracts\RegisterViewResponse as RegisterViewResponseContract;
use LaravelWebauthn\Contracts\UpdateResponse as UpdateResponseContract;
use LaravelWebauthn\Facades\Webauthn as WebauthnFacade;
use LaravelWebauthn\Http\Responses\DestroyResponse;
use LaravelWebauthn\Http\Responses\LoginSuccessResponse;
use LaravelWebauthn\Http\Responses\LoginViewResponse;
use LaravelWebauthn\Http\Responses\RegisterSuccessResponse;
use LaravelWebauthn\Http\Responses\RegisterViewResponse;
use LaravelWebauthn\Http\Responses\UpdateResponse;
use LaravelWebauthn\Services\Webauthn;
use LaravelWebauthn\Services\Webauthn\CredentialAssertionValidator;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Webauthn\AttestationStatement\AndroidKeyAttestationStatementSupport;
use Webauthn\AttestationStatement\AndroidSafetyNetAttestationStatementSupport;
use Webauthn\AttestationStatement\AppleAttestationStatementSupport;
use Webauthn\AttestationStatement\AttestationObjectLoader;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\FidoU2FAttestationStatementSupport;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AttestationStatement\PackedAttestationStatementSupport;
use Webauthn\AttestationStatement\TPMAttestationStatementSupport;
use Webauthn\AuthenticationExtensions\ExtensionOutputCheckerHandler;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\Counter\CounterChecker;
use Webauthn\Counter\ThrowExceptionIfInvalid;
use Webauthn\PublicKeyCredentialLoader;
use Webauthn\PublicKeyCredentialRpEntity;

class WebauthnServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configurePublishing();
        $this->configureRoutes();
        $this->configureResources();
        $this->passwordLessWebauthn();
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(WebauthnFacade::class, Webauthn::class);

        $this->registerResponseBindings();
        $this->bindWebAuthnPackage();
        $this->bindPsrInterfaces();

        $this->mergeConfigFrom(
            __DIR__.'/../config/webauthn.php', 'webauthn'
        );

        $this->app->bind(StatefulGuard::class, fn () => Auth::guard(config('webauthn.guard', null)));
        $this->app->bind('webauthn.log', fn ($app) => $app['log']->channel(config('webauthn.log', config('logging.default'))));
    }

    /**
     * Register the package routes.
     *
     * @psalm-suppress InvalidArgument
     */
    private function configureRoutes(): void
    {
        if (Webauthn::$registersRoutes) {
            $this->app['router']->group([
                'namespace' => 'LaravelWebauthn\Http\Controllers',
                'domain' => config('webauthn.domain', null),
                'prefix' => config('webauthn.prefix', 'webauthn'),
            ], fn () => $this->loadRoutesFrom(__DIR__.'/../routes/routes.php'));
        }
    }

    /**
     * Register the response bindings.
     */
    public function registerResponseBindings(): void
    {
        $this->app->singleton(DestroyResponseContract::class, DestroyResponse::class);
        $this->app->singleton(LoginSuccessResponseContract::class, LoginSuccessResponse::class);
        $this->app->singleton(LoginViewResponseContract::class, LoginViewResponse::class);
        $this->app->singleton(RegisterSuccessResponseContract::class, RegisterSuccessResponse::class);
        $this->app->singleton(RegisterViewResponseContract::class, RegisterViewResponse::class);
        $this->app->singleton(UpdateResponseContract::class, UpdateResponse::class);
    }

    /**
     * Bind all the WebAuthn package services to the Service Container.
     */
    protected function bindWebAuthnPackage(): void
    {
        $this->app->bind(
            PackedAttestationStatementSupport::class,
            fn ($app) => new PackedAttestationStatementSupport(
                $app[CoseAlgorithmManager::class]
            )
        );
        $this->app->bind(
            AndroidSafetyNetAttestationStatementSupport::class,
            fn ($app) => (new AndroidSafetyNetAttestationStatementSupport())
                ->enableApiVerification(
                    $app[ClientInterface::class],
                    $app['config']->get('webauthn.google_safetynet_api_key'),
                    $app[RequestFactoryInterface::class]
                )
        );
        $this->app->bind(
            AttestationStatementSupportManager::class,
            fn ($app) => tap(new AttestationStatementSupportManager(), function ($manager) use ($app) {
                // https://www.w3.org/TR/webauthn/#sctn-none-attestation
                $manager->add($app[NoneAttestationStatementSupport::class]);

                // https://www.w3.org/TR/webauthn/#sctn-fido-u2f-attestation
                $manager->add($app[FidoU2FAttestationStatementSupport::class]);

                // https://www.w3.org/TR/webauthn/#sctn-android-key-attestation
                $manager->add($app[AndroidKeyAttestationStatementSupport::class]);

                // https://www.w3.org/TR/webauthn/#sctn-tpm-attestation
                $manager->add($app[TPMAttestationStatementSupport::class]);

                // https://www.w3.org/TR/webauthn/#sctn-packed-attestation
                $manager->add($app[PackedAttestationStatementSupport::class]);

                // https://www.w3.org/TR/webauthn/#sctn-android-safetynet-attestation
                if ($app['config']->get('webauthn.google_safetynet_api_key') !== null) {
                    $manager->add($app[AndroidSafetyNetAttestationStatementSupport::class]);
                }

                // https://www.w3.org/TR/webauthn/#sctn-apple-anonymous-attestation
                $manager->add($app[AppleAttestationStatementSupport::class]);
            })
        );
        $this->app->bind(
            AttestationObjectLoader::class,
            fn ($app) => tap(new AttestationObjectLoader(
                $app[AttestationStatementSupportManager::class]
            ), fn (AttestationObjectLoader $loader) => $loader->setLogger($app['webauthn.log'])
            )
        );

        $this->app->bind(
            CounterChecker::class,
            fn ($app) => new ThrowExceptionIfInvalid($app['webauthn.log'])
        );

        $this->app->bind(
            AuthenticatorAttestationResponseValidator::class,
            fn ($app) => tap(new AuthenticatorAttestationResponseValidator(
                $app[AttestationStatementSupportManager::class],
                null,
                null,
                $app[ExtensionOutputCheckerHandler::class]
            ), fn (AuthenticatorAttestationResponseValidator $responseValidator) => $responseValidator->setLogger($app['webauthn.log'])
            )
        );
        $this->app->bind(
            AuthenticatorAssertionResponseValidator::class,
            fn ($app) => tap((new AuthenticatorAssertionResponseValidator(
                null,
                null,
                $app[ExtensionOutputCheckerHandler::class],
                $app[CoseAlgorithmManager::class]
            ))
                ->setCounterChecker($app[CounterChecker::class]), fn (AuthenticatorAssertionResponseValidator $responseValidator) => $responseValidator->setLogger($app['webauthn.log'])
            )
        );
        $this->app->bind(
            AuthenticatorSelectionCriteria::class,
            fn ($app) => new AuthenticatorSelectionCriteria(
                $app['config']->get('webauthn.attachment_mode', 'null'),
                $app['config']->get('webauthn.user_verification', 'preferred'),
                $app['config']->get('webauthn.userless')
            )
        );

        $this->app->bind(
            PublicKeyCredentialRpEntity::class,
            fn ($app) => new PublicKeyCredentialRpEntity(
                $app['config']->get('app.name', 'Laravel'),
                $app->make('request')->host(),
                $app['config']->get('webauthn.icon')
            )
        );
        $this->app->bind(
            PublicKeyCredentialLoader::class,
            fn ($app) => tap(new PublicKeyCredentialLoader(
                $app[AttestationObjectLoader::class]
            ), fn (PublicKeyCredentialLoader $loader) => $loader->setLogger($app['webauthn.log'])
            )
        );

        $this->app->bind(
            CoseAlgorithmManager::class,
            fn ($app) => $app[CoseAlgorithmManagerFactory::class]
                ->generate(...$app['config']->get('webauthn.public_key_credential_parameters'))
        );
        $this->app->bind(
            CoseAlgorithmManagerFactory::class,
            fn () => tap(new CoseAlgorithmManagerFactory, function ($factory) {
                // list of existing algorithms
                $algorithms = [
                    RSA\RS1::class,
                    RSA\RS256::class,
                    RSA\RS384::class,
                    RSA\RS512::class,
                    RSA\PS256::class,
                    RSA\PS384::class,
                    RSA\PS512::class,
                    ECDSA\ES256::class,
                    ECDSA\ES256K::class,
                    ECDSA\ES384::class,
                    ECDSA\ES512::class,
                    EdDSA\Ed256::class,
                    EdDSA\Ed512::class,
                    EdDSA\Ed25519::class,
                    EdDSA\EdDSA::class,
                ];

                foreach ($algorithms as $algorithm) {
                    $factory->add((string) $algorithm::identifier(), new $algorithm);
                }
            })
        );
    }

    /**
     * @psalm-suppress UndefinedClass
     * @psalm-suppress PossiblyInvalidArgument
     */
    protected function bindPsrInterfaces(): void
    {
        $this->app->bind(ClientInterface::class, function () {
            if (class_exists(Psr18ClientDiscovery::class) && class_exists(NotFoundException::class)) {
                try {
                    return Psr18ClientDiscovery::find();
                    // @codeCoverageIgnoreStart
                } catch (NotFoundException $e) {
                    app('webauthn.log')->error('Could not find PSR-18 Client Factory.', ['exception' => $e]);
                    throw new BindingResolutionException('Unable to resolve PSR-18 Client Factory. Please install a psr/http-client-implementation implementation like \'guzzlehttp/guzzle\'.');
                }
            }

            throw new BindingResolutionException('Unable to resolve PSR-18 request. Please install php-http/discovery and implementations for psr/http-client-implementation.');
            // @codeCoverageIgnoreEnd
        });

        $this->app->bind(RequestFactoryInterface::class, function () {
            if (class_exists(Psr17FactoryDiscovery::class) && class_exists(NotFoundException::class)) {
                try {
                    return Psr17FactoryDiscovery::findRequestFactory();
                    // @codeCoverageIgnoreStart
                } catch (NotFoundException $e) {
                    app('webauthn.log')->error('Could not find PSR-17 Request Factory.', ['exception' => $e]);
                    throw new BindingResolutionException('Unable to resolve PSR-17 Request Factory. Please install psr/http-factory-implementation implementation like \'guzzlehttp/psr7\'.');
                }
            }

            throw new BindingResolutionException('Unable to resolve PSR-17 request. Please install php-http/discovery and implementations for psr/http-factory-implementation.');
            // @codeCoverageIgnoreEnd
        });

        $this->app->bind(ServerRequestInterface::class, function ($app) {
            if (class_exists(PsrHttpFactory::class)) {
                return $app[PsrHttpFactory::class]
                    ->createRequest($app->make('request'));
            }

            if (class_exists(\GuzzleHttp\Psr7\ServerRequest::class)) {
                return \GuzzleHttp\Psr7\ServerRequest::fromGlobals();
            }

            throw new BindingResolutionException('Unable to resolve PSR-7 Server Request. Please install the guzzlehttp/psr7 or symfony/psr-http-message-bridge, php-http/discovery and a psr/http-factory-implementation implementation.'); // @codeCoverageIgnore
        });

        if (class_exists(PsrHttpFactory::class)) {
            $this->app->bind(PsrHttpFactory::class, function () {
                if (class_exists(\Nyholm\Psr7\Factory\Psr17Factory::class) && class_exists(PsrHttpFactory::class)) {
                    /**
                     * @var ServerRequestFactoryInterface|StreamFactoryInterface|UploadedFileFactoryInterface|ResponseFactoryInterface
                     */
                    $psr17Factory = new \Nyholm\Psr7\Factory\Psr17Factory;

                    return new PsrHttpFactory($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
                } elseif (class_exists(Psr17FactoryDiscovery::class)
                    && class_exists(NotFoundException::class)
                    && class_exists(PsrHttpFactory::class)) {
                    try {
                        $uploadFileFactory = Psr17FactoryDiscovery::findUploadedFileFactory();
                        $responseFactory = Psr17FactoryDiscovery::findResponseFactory();
                        $serverRequestFactory = Psr17FactoryDiscovery::findServerRequestFactory();
                        $streamFactory = Psr17FactoryDiscovery::findStreamFactory();

                        return new PsrHttpFactory($serverRequestFactory, $streamFactory, $uploadFileFactory, $responseFactory);
                        // @codeCoverageIgnoreStart
                    } catch (NotFoundException $e) {
                        app('webauthn.log')->error('Could not find PSR-17 Factory.', ['exception' => $e]);
                    }
                }

                throw new BindingResolutionException('Unable to resolve PSR-17 Factory. Please install psr/http-factory-implementation implementation like \'guzzlehttp/psr7\'.');
                // @codeCoverageIgnoreEnd
            });
        }
    }

    private function passwordLessWebauthn(): void
    {
        $this->app['auth']->provider('webauthn', fn ($app, array $config) => new EloquentWebAuthnProvider(
            $app['config'],
            $app[CredentialAssertionValidator::class],
            $app[Hasher::class],
            $config['model']
        )
        );
    }

    /**
     * Register the package's publishable resources.
     */
    private function configurePublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/webauthn.php' => config_path('webauthn.php'),
            ], 'webauthn-config');

            $this->publishes([
                __DIR__.'/../database/migrations/' => database_path('migrations'),
            ], 'webauthn-migrations');

            $this->publishes([
                __DIR__.'/../resources/js' => public_path('vendor/webauthn'),
            ], 'webauthn-assets');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/webauthn'),
            ], 'webauthn-views');
        }
    }

    /**
     * Register other package's resources.
     */
    private function configureResources(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views/', 'webauthn');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'webauthn');
    }
}
