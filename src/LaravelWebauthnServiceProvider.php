<?php

namespace LaravelWebauthn;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class LaravelWebauthnServiceProvider extends ServiceProvider
{
    /**
     * Name of the middleware group.
     *
     * @var string
     */
    private const MIDDLEWARE_GROUP = 'laravel-webauthn';

    /**
     * All of the container singletons that should be registered.
     *
     * @var array
     */
    public $singletons = [
        Webauthn::class => Webauthn::class,
    ];

    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot()
    {
        Route::middlewareGroup(self::MIDDLEWARE_GROUP, config('webauthn.middleware', []));

        $this->registerRoutes();
        $this->registerPublishing();
    }

    /**
     * Register the package routes.
     *
     * @psalm-suppress InvalidArgument
     *
     * @return void
     */
    private function registerRoutes()
    {
        Route::group($this->routeConfiguration(), function (\Illuminate\Routing\Router $router) : void {
            $router->get('auth', 'WebauthnController@login')->name('webauthn.login');
            $router->post('auth', 'WebauthnController@auth');

            $router->get('register', 'WebauthnController@register')->name('webauthn.register');
            $router->post('register', 'WebauthnController@create');
            $router->delete('{id}', 'WebauthnController@remove');
        });
    }

    /**
     * Get the route group configuration array.
     *
     * @return array
     */
    private function routeConfiguration()
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
    private function registerPublishing()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/webauthn.php' => config_path('webauthn.php'),
            ], 'webauthn-config');

            $this->publishes([
                __DIR__.'/../database/migrations/' => base_path('/database/migrations'),
            ], 'webauthn-migrations');
        }
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/webauthn.php', 'webauthn'
        );

        /** @var \Illuminate\Contracts\Foundation\Application */
        $app = $this->app;

        $app->bind(
            \Webauthn\PublicKeyCredentialSourceRepository::class,
            \LaravelWebauthn\Services\Webauthn\CredentialRepository::class
        );

        if ($app->runningInConsole()) {
            $this->commands([
                Console\PublishCommand::class,
            ]);
        }
    }
}
