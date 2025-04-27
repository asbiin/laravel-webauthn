<?php

namespace LaravelWebauthn\Tests\Unit\Actions;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use LaravelWebauthn\Actions\PrepareCreationData;
use LaravelWebauthn\Facades\Webauthn;
use LaravelWebauthn\Services\Webauthn\PublicKeyCredentialCreationOptions;
use LaravelWebauthn\Tests\FeatureTestCase;
use PHPUnit\Framework\Attributes\Test;
use Webauthn\PublicKeyCredentialCreationOptions as PublicKeyCredentialCreationOptionsBase;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;

class PrepareCreationDataTest extends FeatureTestCase
{
    use DatabaseTransactions;

    #[Test]
    public function it_get_attestation()
    {
        $user = $this->user();

        Webauthn::shouldReceive('prepareAttestation')->andReturn(PublicKeyCredentialCreationOptions::create(new PublicKeyCredentialCreationOptionsBase(
            $this->mock(PublicKeyCredentialRpEntity::class),
            $this->mock(PublicKeyCredentialUserEntity::class),
            'challenge',
            []
        )));
        Webauthn::shouldReceive('canRegister')->andReturn(true);

        $result = app(PrepareCreationData::class)($user);

        $this->assertNotNull($result);
    }

    #[Test]
    public function it_fails()
    {
        $user = $this->user();

        Webauthn::shouldReceive('canRegister')->andReturn(false);
        Webauthn::shouldReceive('username')->andReturn('user');

        $this->expectException(ValidationException::class);
        app(PrepareCreationData::class)($user);
    }
}
