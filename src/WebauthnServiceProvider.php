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
use LaravelWebauthn\Contracts\FailedKeyConfirmedResponse as FailedKeyConfirmedResponseContract;
use LaravelWebauthn\Contracts\KeyConfirmedResponse as KeyConfirmedResponseContract;
use LaravelWebauthn\Contracts\LoginSuccessResponse as LoginSuccessResponseContract;
use LaravelWebauthn\Contracts\LoginViewResponse as LoginViewResponseContract;
use LaravelWebauthn\Contracts\RegisterSuccessResponse as RegisterSuccessResponseContract;
use LaravelWebauthn\Contracts\RegisterViewResponse as RegisterViewResponseContract;
use LaravelWebauthn\Contracts\UpdateResponse as UpdateResponseContract;
use LaravelWebauthn\Events\EventDispatcher;
use LaravelWebauthn\Facades\Webauthn as WebauthnFacade;
use LaravelWebauthn\Http\Responses\DestroyResponse;
use LaravelWebauthn\Http\Responses\FailedKeyConfirmedResponse;
use LaravelWebauthn\Http\Responses\KeyConfirmedResponse;
use LaravelWebauthn\Http\Responses\LoginSuccessResponse;
use LaravelWebauthn\Http\Responses\LoginViewResponse;
use LaravelWebauthn\Http\Responses\RegisterSuccessResponse;
use LaravelWebauthn\Http\Responses\RegisterViewResponse;
use LaravelWebauthn\Http\Responses\UpdateResponse;
use LaravelWebauthn\Services\Webauthn;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\Serializer\SerializerInterface;
use Webauthn\AttestationStatement\AndroidKeyAttestationStatementSupport;
use Webauthn\AttestationStatement\AppleAttestationStatementSupport;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\FidoU2FAttestationStatementSupport;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AttestationStatement\PackedAttestationStatementSupport;
use Webauthn\AttestationStatement\TPMAttestationStatementSupport;
use Webauthn\AuthenticationExtensions\ExtensionOutputCheckerHandler;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use Webauthn\Counter\CounterChecker;
use Webauthn\Counter\ThrowExceptionIfInvalid;
use Webauthn\Event\CanDispatchEvents;
use Webauthn\MetadataService\CanLogData;
use Webauthn\PublicKeyCredentialRpEntity;

/**
 * @psalm-suppress UnusedClass
 */
class WebauthnServiceProvider extends ServiceProvider
{
    /**
     * All of the container singletons that should be registered.
     *
     * @var array
     */
    public $singletons = [
        WebauthnFacade::class => Webauthn::class,
        DestroyResponseContract::class => DestroyResponse::class,
        LoginSuccessResponseContract::class => LoginSuccessResponse::class,
        LoginViewResponseContract::class => LoginViewResponse::class,
        RegisterSuccessResponseContract::class => RegisterSuccessResponse::class,
        RegisterViewResponseContract::class => RegisterViewResponse::class,
        UpdateResponseContract::class => UpdateResponse::class,
        KeyConfirmedResponseContract::class => KeyConfirmedResponse::class,
        FailedKeyConfirmedResponseContract::class => FailedKeyConfirmedResponse::class,
        EventDispatcherInterface::class => EventDispatcher::class,
    ];

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
    #[\Override]
    public function register(): void
    {
        $this->bindWebAuthnPackage();
        $this->bindPsrInterfaces();

        $this->mergeConfigFrom(
            __DIR__.'/../config/webauthn.php', 'webauthn'
        );

        $this->app->bind('webauthn.log', fn ($app) => $app['log']->channel(config('webauthn.log', config('logging.default'))));
        $this->app->bind(StatefulGuard::class, fn () => Auth::guard(config('webauthn.guard', null)));
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
     * Bind all the WebAuthn package services to the Service Container.
     */
    protected function bindWebAuthnPackage(): void
    {
        $this->app->resolving(CanDispatchEvents::class, fn (CanDispatchEvents $object) => $object->setEventDispatcher($this->app[EventDispatcherInterface::class]));
        $this->app->resolving(CanLogData::class, fn (CanLogData $object) => $object->setLogger($this->app['webauthn.log']));

        $this->app->bind(
            AttestationStatementSupportManager::class,
            fn ($app) => tap(new AttestationStatementSupportManager, function ($manager) use ($app) {
                $supports = [
                    // https://www.w3.org/TR/webauthn/#sctn-packed-attestation
                    PackedAttestationStatementSupport::class,
                    // https://www.w3.org/TR/webauthn/#sctn-tpm-attestation
                    TPMAttestationStatementSupport::class,
                    // https://www.w3.org/TR/webauthn/#sctn-android-key-attestation
                    AndroidKeyAttestationStatementSupport::class,
                    // https://www.w3.org/TR/webauthn/#sctn-fido-u2f-attestation
                    FidoU2FAttestationStatementSupport::class,
                    // https://www.w3.org/TR/webauthn/#sctn-none-attestation
                    NoneAttestationStatementSupport::class,
                    // https://www.w3.org/TR/webauthn/#sctn-apple-anonymous-attestation
                    AppleAttestationStatementSupport::class,
                ];
                foreach ($supports as $support) {
                    $manager->add($app[$support]);
                }
            })
        );
        $this->app->bind(
            SerializerInterface::class,
            fn ($app) => (new \Webauthn\Denormalizer\WebauthnSerializerFactory($app[AttestationStatementSupportManager::class]))->create()
        );

        $this->app->bind(
            CounterChecker::class,
            fn ($app) => new ThrowExceptionIfInvalid(
                logger: $app['webauthn.log']
            )
        );

        $this->app->bind(
            CeremonyStepManagerFactory::class,
            fn ($app) => tap(new CeremonyStepManagerFactory, function (CeremonyStepManagerFactory $factory) use ($app) {
                $factory->setExtensionOutputCheckerHandler($app[ExtensionOutputCheckerHandler::class]);
                $factory->setAlgorithmManager($app[CoseAlgorithmManager::class]);
                $factory->setCounterChecker($app[CounterChecker::class]);
                $factory->setAttestationStatementSupportManager($app[AttestationStatementSupportManager::class]);
                // $factory->setAllowedOrigins(
                //     allowedOrigins: $app['config']->get('webauthn.allowed_origins'),
                //     allowSubdomains: $app['config']->get('webauthn.allow_subdomains')
                // );
            })
        );
        $this->app->bind(
            AuthenticatorAttestationResponseValidator::class,
            fn ($app) => new AuthenticatorAttestationResponseValidator(
                ceremonyStepManager: ($app[CeremonyStepManagerFactory::class])->creationCeremony(),
            )
        );
        $this->app->bind(
            AuthenticatorAssertionResponseValidator::class,
            fn ($app) => (new AuthenticatorAssertionResponseValidator(
                ceremonyStepManager: ($app[CeremonyStepManagerFactory::class])->requestCeremony()
            ))
        );

        $this->app->bind(
            AuthenticatorSelectionCriteria::class,
            fn ($app) => new AuthenticatorSelectionCriteria(
                authenticatorAttachment: $app['config']->get('webauthn.attachment_mode'),
                userVerification: $app['config']->get('webauthn.user_verification', 'preferred'),
                residentKey: $app['config']->get('webauthn.resident_key', 'preferred')
            )
        );

        $this->app->bind(
            PublicKeyCredentialRpEntity::class,
            fn ($app) => new PublicKeyCredentialRpEntity(
                name: $app['config']->get('app.name', 'Laravel'),
                id: $app->make('request')->host(),
                icon: $app['config']->get('webauthn.icon')
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
                     *
                     * @phpstan-ignore varTag.nativeType
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
