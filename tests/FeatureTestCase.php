<?php

namespace LaravelWebauthn\Tests;

use Illuminate\Contracts\Auth\Authenticatable;
use Orchestra\Testbench\TestCase;

class FeatureTestCase extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            \LaravelWebauthn\SingletonServiceProvider::class,
            \LaravelWebauthn\WebauthnServiceProvider::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadLaravelMigrations('testbench');

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->withFactories(__DIR__.'/database/factories');
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    /**
     * Resolve application Core implementation.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function resolveApplicationCore($app)
    {
        parent::resolveApplicationCore($app);

        $app->detectEnvironment(function () {
            return 'testing';
        });
    }

    /**
     * Resolve application HTTP Kernel implementation.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function resolveApplicationHttpKernel($app)
    {
        $app->singleton(
            \Illuminate\Contracts\Http\Kernel::class,
            \Orchestra\Testbench\Http\Kernel::class
        );
    }

    /**
     * Create a user and sign in as that user. If a user
     * object is passed, then sign in as that user.
     *
     * @param null $user
     * @return Authenticatable
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
     * @return Authenticatable
     */
    public function user()
    {
        $user = new Authenticated();
        $user->email = 'john@doe.com';

        return $user;
    }
}

class Authenticated implements Authenticatable
{
    public $email;

    protected static $ids;
    protected $id;

    public function __construct()
    {
        $this->id = ++self::$ids;
    }

    public function getAuthIdentifierName()
    {
        return 'getAuthIdentifier';
    }

    public function getAuthIdentifier()
    {
        return (string) $this->id;
    }

    public function getAuthPassword()
    {
        return 'secret';
    }

    public function getRememberToken()
    {
        return 'token';
    }

    public function setRememberToken($value)
    {
    }

    public function getRememberTokenName()
    {
    }
}
