<?php

namespace LaravelWebauthn;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class LaravelWebauthnServiceProvider extends ServiceProvider
{
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
        Route::middlewareGroup('webauthn', config('webauthn.middleware', ['web', 'auth']));

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
            $router->get('auth', [
                'uses' => 'WebauthnController@login',
                'middleware' => 'auth',
            ]);
            $router->post('auth', [
                'uses' => 'WebauthnController@auth',
                'middleware' => 'auth',
            ]);

            $router->get('register', [
                'uses' => 'WebauthnController@register',
                'middleware' => 'auth',
            ]);
            $router->post('register', [
                'uses' => 'WebauthnController@create',
                'middleware' => 'auth',
            ]);
            $router->delete('{id}', [
                'uses' => 'WebauthnController@remove',
                'middleware' => 'auth',
            ]);
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
            'middleware' => 'webauthn',
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

        if ($app->runningInConsole()) {
            $this->commands([
                Console\PublishCommand::class,
            ]);
        }
    }
}
