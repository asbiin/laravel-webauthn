<?php

namespace LaravelWebauthn\Tests;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Orchestra\Testbench\TestCase;

class FeatureTestCase extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [
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

    public static function setUpBeforeClass(): void
    {
        if (! class_exists('\Illuminate\Testing\TestResponse') && class_exists('\Illuminate\Foundation\Testing\TestResponse')) {
            class_alias('\Illuminate\Foundation\Testing\TestResponse', '\Illuminate\Testing\TestResponse');
        }
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
     * @param  null  $user
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
        // return User::create([
        //     'name' => 'John Doe',
        //     'email' => 'john@doe.com',
        //     'password' => 'test',
        //     'email_verified_at' => null,
        //     'remember_token' => null,
        // ]);
    }
}

class User extends Authenticatable
{
    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified_at',
        'remember_token',
    ];

    // public $email;

    // protected static $ids;
    // protected $id;

    // public function __construct()
    // {
    //     $this->id = ++self::$ids;
    // }

    // public function getAuthIdentifierName()
    // {
    //     return 'getAuthIdentifier';
    // }

    // public function getAuthIdentifier()
    // {
    //     return (string) $this->id;
    // }

    // public function getAuthPassword()
    // {
    //     return 'secret';
    // }

    // public function getRememberToken()
    // {
    //     return 'token';
    // }

    // public function setRememberToken($value)
    // {
    // }

    // public function getRememberTokenName()
    // {
    // }

    // public function newQuery() {

    // }
}
