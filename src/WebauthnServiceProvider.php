<?php

namespace LaravelWebauthn;

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
use LaravelWebauthn\Services\Webauthn;
use LaravelWebauthn\Services\Webauthn\CredentialRepository;

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
        $this->app->singleton(CredentialRepository::class, CredentialRepository::class);
        $this->app->singleton(WebauthnFacade::class, Webauthn::class);

        $this->registerResponseBindings();

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
     * Register the package's publishable resources.
     *
     * @return void
     */
    private function configurePublishing()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\PublishCommand::class,
            ]);

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
