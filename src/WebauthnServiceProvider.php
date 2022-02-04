<?php

namespace LaravelWebauthn;

use Cose\Algorithm\Manager as CoseAlgorithmManager;
use Cose\Algorithm\ManagerFactory as CoseAlgorithmManagerFactory;
use Cose\Algorithm\Signature\ECDSA;
use Cose\Algorithm\Signature\EdDSA;
use Cose\Algorithm\Signature\RSA;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use LaravelWebauthn\Contracts\DestroyResponse as DestroyResponseContract;
use LaravelWebauthn\Contracts\LoginSuccessResponse as LoginSuccessResponseContract;
use LaravelWebauthn\Contracts\LoginViewResponse as LoginViewResponseContract;
use LaravelWebauthn\Contracts\RegisterSuccessResponse as RegisterSuccessResponseContract;
use LaravelWebauthn\Contracts\RegisterViewResponse as RegisterViewResponseContract;
use LaravelWebauthn\Contracts\UpdateResponse as UpdateResponseContract;
use LaravelWebauthn\Facades\Webauthn as WebauthnFacade;
use LaravelWebauthn\Http\Controllers\AuthenticateController;
use LaravelWebauthn\Http\Controllers\WebauthnKeyController;
use LaravelWebauthn\Http\Responses\DestroyResponse;
use LaravelWebauthn\Http\Responses\LoginSuccessResponse;
use LaravelWebauthn\Http\Responses\LoginViewResponse;
use LaravelWebauthn\Http\Responses\RegisterSuccessResponse;
use LaravelWebauthn\Http\Responses\RegisterViewResponse;
use LaravelWebauthn\Http\Responses\UpdateResponse;
use LaravelWebauthn\Services\Http\PsrHelper;
use LaravelWebauthn\Services\Webauthn;
use LaravelWebauthn\Services\Webauthn\CredentialRepository;
use Webauthn\AttestationStatement\AndroidKeyAttestationStatementSupport;
use Webauthn\AttestationStatement\AndroidSafetyNetAttestationStatementSupport;
use Webauthn\AttestationStatement\AppleAttestationStatementSupport;
use Webauthn\AttestationStatement\AttestationObjectLoader;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\FidoU2FAttestationStatementSupport;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AttestationStatement\PackedAttestationStatementSupport;
use Webauthn\AttestationStatement\TPMAttestationStatementSupport;
use Webauthn\AuthenticationExtensions\AuthenticationExtensionsClientInputs;
use Webauthn\AuthenticationExtensions\ExtensionOutputCheckerHandler;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\Counter\CounterChecker;
use Webauthn\Counter\ThrowExceptionIfInvalid;
use Webauthn\PublicKeyCredentialLoader;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialSourceRepository;
use Webauthn\TokenBinding\IgnoreTokenBindingHandler;
use Webauthn\TokenBinding\TokenBindingHandler;

class WebauthnServiceProvider extends ServiceProvider
{
    /**
     * Name of the middleware group.
     *
     * @var string
     */
    private const MIDDLEWARE_GROUP = 'laravel-webauthn';

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->configurePublishing();
        $this->configureRoutes();
        $this->configureResources();
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(WebauthnFacade::class, Webauthn::class);

        $this->registerResponseBindings();
        $this->bindWebAuthnPackage();

        $this->mergeConfigFrom(
            __DIR__.'/../config/webauthn.php', 'webauthn'
        );
    }

    /**
     * Register the package routes.
     *
     * @psalm-suppress InvalidArgument
     *
     * @return void
     */
    private function configureRoutes()
    {
        Route::middlewareGroup(self::MIDDLEWARE_GROUP, config('webauthn.middleware', []));
        Route::group($this->routeAttributes(), function (\Illuminate\Routing\Router $router): void {
            $router->get('auth', [AuthenticateController::class, 'login'])->name('webauthn.login');
            $router->post('auth', [AuthenticateController::class, 'auth'])->name('webauthn.auth');

            $router->get('keys/create', [WebauthnKeyController::class, 'create'])->name('webauthn.create');
            $router->post('keys', [WebauthnKeyController::class, 'store'])->name('webauthn.store');
            $router->delete('keys/{id}', [WebauthnKeyController::class, 'destroy'])->name('webauthn.destroy');
            $router->put('keys/{id}', [WebauthnKeyController::class, 'update'])->name('webauthn.update');
        });
    }

    /**
     * Register the response bindings.
     *
     * @return void
     */
    public function registerResponseBindings()
    {
        $this->app->singleton(DestroyResponseContract::class, DestroyResponse::class);
        $this->app->singleton(LoginSuccessResponseContract::class, LoginSuccessResponse::class);
        $this->app->singleton(LoginViewResponseContract::class, LoginViewResponse::class);
        $this->app->singleton(RegisterSuccessResponseContract::class, RegisterSuccessResponse::class);
        $this->app->singleton(RegisterViewResponseContract::class, RegisterViewResponse::class);
        $this->app->singleton(UpdateResponseContract::class, UpdateResponse::class);
    }

    /**
     * Get the route group configuration array.
     *
     * @return array
     */
    private function routeAttributes()
    {
        return [
            'middleware' => self::MIDDLEWARE_GROUP,
            'domain' => config('webauthn.domain', null),
            'namespace' => 'LaravelWebauthn\Http\Controllers',
            'prefix' => config('webauthn.prefix', 'webauthn'),
        ];
    }

    /**
     * Bind all the WebAuthn package services to the Service Container.
     *
     * @return void
     */
    protected function bindWebAuthnPackage(): void
    {
        $this->app->bind(PublicKeyCredentialSourceRepository::class, CredentialRepository::class);
        $this->app->bind(TokenBindingHandler::class, IgnoreTokenBindingHandler::class);
        $this->app->bind(ExtensionOutputCheckerHandler::class, ExtensionOutputCheckerHandler::class);
        $this->app->bind(AuthenticationExtensionsClientInputs::class, AuthenticationExtensionsClientInputs::class);

        $this->app->bind(NoneAttestationStatementSupport::class, NoneAttestationStatementSupport::class);
        $this->app->bind(FidoU2FAttestationStatementSupport::class, FidoU2FAttestationStatementSupport::class);
        $this->app->bind(AndroidKeyAttestationStatementSupport::class, AndroidKeyAttestationStatementSupport::class);
        $this->app->bind(TPMAttestationStatementSupport::class, TPMAttestationStatementSupport::class);
        $this->app->bind(AppleAttestationStatementSupport::class, AppleAttestationStatementSupport::class);
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
                    PsrHelper::getClient(),
                    $app['config']->get('webauthn.google_safetynet_api_key'),
                    PsrHelper::getRequestFactory()
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
            fn ($app) => (new AttestationObjectLoader(
                    $app[AttestationStatementSupportManager::class]
                ))
                    ->setLogger($app['log'])
        );

        $this->app->bind(
            PublicKeyCredentialLoader::class,
            fn ($app) => (new PublicKeyCredentialLoader(
                    $app[AttestationObjectLoader::class]
                ))
                    ->setLogger($app['log'])
        );

        $this->app->bind(
            CoseAlgorithmManagerFactory::class,
            fn () => tap(new CoseAlgorithmManagerFactory, function ($factory) {
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
                    EdDSA\ED256::class,
                    EdDSA\ED512::class,
                    EdDSA\Ed25519::class,
                    EdDSA\EdDSA::class,
                ];

                foreach ($algorithms as $algorithm) {
                    $factory->add((string) $algorithm::identifier(), new $algorithm);
                }
            })
        );

        $this->app->bind(
            CoseAlgorithmManager::class,
            fn ($app) => $app[CoseAlgorithmManagerFactory::class]
                ->create($app['config']->get('webauthn.public_key_credential_parameters'))
        );

        $this->app->bind(
            AuthenticatorAttestationResponseValidator::class,
            fn ($app) => (new AuthenticatorAttestationResponseValidator(
                    $app[AttestationStatementSupportManager::class],
                    $app[PublicKeyCredentialSourceRepository::class],
                    $app[TokenBindingHandler::class],
                    $app[ExtensionOutputCheckerHandler::class]
                ))
                ->setLogger($app['log'])
        );

        $this->app->bind(
            CounterChecker::class,
            fn ($app) => new ThrowExceptionIfInvalid($app['log'])
        );

        $this->app->bind(
            AuthenticatorAssertionResponseValidator::class,
            fn ($app) => (new AuthenticatorAssertionResponseValidator(
                    $app[PublicKeyCredentialSourceRepository::class],
                    $app[TokenBindingHandler::class],
                    $app[ExtensionOutputCheckerHandler::class],
                    $app[CoseAlgorithmManager::class]
                ))
                ->setCounterChecker($app[CounterChecker::class])
                ->setLogger($app['log'])
        );

        $this->app->bind(
            PublicKeyCredentialRpEntity::class,
            fn ($app) => new PublicKeyCredentialRpEntity(
                    $app['config']->get('app.name', 'Laravel'),
                    $app['request']->getHost(),
                    $app['config']->get('webauthn.icon')
                )
        );

        $this->app->bind(
            AuthenticatorSelectionCriteria::class,
            fn ($app) => tap(new AuthenticatorSelectionCriteria(), function ($authenticatorSelectionCriteria) use ($app) {
                $authenticatorSelectionCriteria
                        ->setAuthenticatorAttachment($app['config']->get('webauthn.attachment_mode', 'null'))
                        ->setUserVerification($app['config']->get('webauthn.user_verification', 'preferred'));

                if (($userless = $app['config']->get('webauthn.userless')) !== null) {
                    $authenticatorSelectionCriteria->setResidentKey($userless);
                }
            })
        );
    }

    /**
     * Register the package's publishable resources.
     *
     * @return void
     */
    private function configurePublishing()
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
     *
     * @return void
     */
    private function configureResources()
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views/', 'webauthn');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'webauthn');
    }
}
