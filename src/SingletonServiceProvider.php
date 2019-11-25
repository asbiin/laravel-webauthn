<?php

namespace LaravelWebauthn;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class SingletonServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        foreach ($this->provides() as $singleton) {
            $this->app->singleton($singleton, $singleton);
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            \LaravelWebauthn\Services\Webauthn\CredentialRepository::class,
            \LaravelWebauthn\Services\Webauthn::class,
        ];
    }
}
