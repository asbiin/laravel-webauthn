<?php

namespace LaravelWebauthn\Tests\Unit\Actions;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use LaravelWebauthn\Actions\PrepareCreationData;
use LaravelWebauthn\Facades\Webauthn;
use LaravelWebauthn\Tests\FeatureTestCase;
use Webauthn\PublicKeyCredentialCreationOptions;

class PrepareCreationDataTest extends FeatureTestCase
{
    use DatabaseTransactions;

    /**
     * @test
     */
    public function it_get_attestation()
    {
        $user = $this->user();

        Webauthn::shouldReceive('prepareAttestation')->andReturn($this->mock(PublicKeyCredentialCreationOptions::class));
        Webauthn::shouldReceive('canRegister')->andReturn(true);

        $result = app(PrepareCreationData::class)($user);

        $this->assertNotNull($result);
    }

    /**
     * @test
     */
    public function it_fails()
    {
        $user = $this->user();

        Webauthn::shouldReceive('canRegister')->andReturn(false);
        Webauthn::shouldReceive('username')->andReturn('user');

        $this->expectException(ValidationException::class);
        app(PrepareCreationData::class)($user);
    }
}
