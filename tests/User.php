<?php

namespace LaravelWebauthn\Tests;

use Illuminate\Foundation\Auth\User as Authenticatable;
use LaravelWebauthn\WebauthnAuthenticatable;

class User extends Authenticatable
{
    use WebauthnAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified_at',
        'remember_token',
    ];
}
