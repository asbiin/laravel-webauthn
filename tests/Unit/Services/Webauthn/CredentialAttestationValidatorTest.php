<?php

namespace LaravelWebauthn\Tests\Unit\Services\Webauthn;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use LaravelWebauthn\Services\Webauthn\CreationOptionsFactory;
use LaravelWebauthn\Services\Webauthn\CredentialAttestationValidator;
use LaravelWebauthn\Tests\FeatureTestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Serializer\SerializerInterface;
use Webauthn\AttestationStatement\AttestationObject;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\CollectedClientData;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\TrustPath\EmptyTrustPath;
use Webauthn\TrustPath\TrustPath;

class CredentialAttestationValidatorTest extends FeatureTestCase
{
    use DatabaseTransactions;

    #[Test]
    public function create_credential_attestation_validator()
    {
        $user = $this->user();

        $option = app(CreationOptionsFactory::class)($user);
        $creds = new PublicKeyCredential('public-key', 'id', new AuthenticatorAttestationResponse(
            $this->mock(CollectedClientData::class),
            $this->mock(AttestationObject::class)
        ));
        $response = $this->mock(PublicKeyCredentialSource::class);

        $this->mock(SerializerInterface::class, function ($mock) use ($option, $creds) {
            $mock->shouldReceive('deserialize')
                ->withSomeOfArgs(TrustPath::class)
                ->andReturn(new EmptyTrustPath);
            $mock->shouldReceive('deserialize')
                ->withSomeOfArgs(PublicKeyCredential::class)
                ->andReturn($creds);
            $mock->shouldReceive('deserialize')
                ->withSomeOfArgs(PublicKeyCredentialCreationOptions::class)
                ->andReturn($option->data);
        });
        $this->mock(AuthenticatorAttestationResponseValidator::class, function ($mock) use ($response) {
            $mock->shouldReceive('check')
                ->andReturn($response);
        });

        $data = json_decode(json_encode($creds), true);
        $test = app(CredentialAttestationValidator::class);
        $result = $test($user, $data);

        $this->assertEquals($response, $result);
    }

    #[Test]
    public function create_credential_attestation_validator_and_fail()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No public key credential found');

        $user = $this->user();

        $creds = new PublicKeyCredential('public-key', 'id', new AuthenticatorAttestationResponse(
            $this->mock(CollectedClientData::class),
            $this->mock(AttestationObject::class)
        ));

        $this->mock(SerializerInterface::class, function ($mock) use ($creds) {
            $mock->shouldReceive('deserialize')
                ->withSomeOfArgs(TrustPath::class)
                ->andReturn(new EmptyTrustPath);
            $mock->shouldReceive('deserialize')
                ->withSomeOfArgs(PublicKeyCredential::class)
                ->andReturn($creds);
        });

        $data = json_decode(json_encode($creds), true);
        $test = app(CredentialAttestationValidator::class);
        $test($user, $data);

        $this->fail('No exception thrown');
    }
}
