<?php

namespace LaravelWebauthn\Tests;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Application;
use LaravelWebauthn\WebauthnServiceProvider;
use Orchestra\Testbench\TestCase;

class FeatureTestCase extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            WebauthnServiceProvider::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadLaravelMigrations('testbench');

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->withFactories(__DIR__.'/database/factories');
    }

    public static function setUpBeforeClass(): void
    {
        if (! class_exists('\Illuminate\Testing\TestResponse') && class_exists('\Illuminate\Foundation\Testing\TestResponse')) {
            class_alias('\Illuminate\Foundation\Testing\TestResponse', '\Illuminate\Testing\TestResponse');
        }
    }

    /**
     * Define environment setup.
     *
     * @param  Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    /**
     * Resolve application Core implementation.
     *
     * @param  Application  $app
     * @return void
     */
    protected function resolveApplicationCore($app)
    {
        parent::resolveApplicationCore($app);

        $app->detectEnvironment(fn () => 'testing');
    }

    /**
     * Resolve application HTTP Kernel implementation.
     *
     * @param  Application  $app
     * @return void
     */
    protected function resolveApplicationHttpKernel($app)
    {
        $app->singleton(
            Kernel::class,
            \Orchestra\Testbench\Http\Kernel::class
        );
    }

    /**
     * Create a user and sign in as that user. If a user
     * object is passed, then sign in as that user.
     *
     * @return User
     */
    public function signIn($user = null)
    {
        if (is_null($user)) {
            $user = $this->user();
        }

        $this->be($user);

        return $user;
    }

    /**
     * Create a user.
     *
     * @return User
     */
    public function user()
    {
        return factory(User::class)->create();
    }
}
