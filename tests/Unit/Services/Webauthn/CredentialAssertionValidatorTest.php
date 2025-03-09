<?php

namespace LaravelWebauthn\Tests\Unit\Services\Webauthn;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Jose\Component\Core\Util\Base64UrlSafe;
use LaravelWebauthn\Models\WebauthnKey;
use LaravelWebauthn\Services\Webauthn\CredentialAssertionValidator;
use LaravelWebauthn\Services\Webauthn\RequestOptionsFactory;
use LaravelWebauthn\Tests\FeatureTestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Serializer\SerializerInterface;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorData;
use Webauthn\CollectedClientData;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\TrustPath\EmptyTrustPath;
use Webauthn\TrustPath\TrustPath;

class CredentialAssertionValidatorTest extends FeatureTestCase
{
    use DatabaseTransactions;

    #[Test]
    public function create_credential_assertion_validator()
    {
        $user = $this->user();
        $webauthnKey = factory(WebauthnKey::class)->create([
            'user_id' => $user->getAuthIdentifier(),
        ]);

        $this->mock(Request::class, function ($mock) {
            $mock->shouldReceive('host')
                ->andReturn('localhost');
            $mock->shouldReceive('ip')
                ->andReturn('127.0.0.1');
        });

        $option = app(RequestOptionsFactory::class)($user);

        $this->mock(SerializerInterface::class, function ($mock) use ($webauthnKey, $option) {
            $mock->shouldReceive('deserialize')
                ->withSomeOfArgs(TrustPath::class)
                ->andReturn(new EmptyTrustPath);
            $mock->shouldReceive('deserialize')
                ->withSomeOfArgs(PublicKeyCredential::class)
                ->andReturn(new PublicKeyCredential('public-key', $webauthnKey->credentialId, new AuthenticatorAssertionResponse(
                    $this->mock(CollectedClientData::class),
                    $this->mock(AuthenticatorData::class),
                    'signature',
                    'userHandle'
                )));
            $mock->shouldReceive('deserialize')
                ->withSomeOfArgs(PublicKeyCredentialRequestOptions::class)
                ->andReturn($option->data);
            $mock->shouldReceive('serialize')
                ->andReturn('{"challenge":"KTWMgB3ND1SbaoM8xEBZvbR1Y5Ehm5gC5p2t73Nd15g","rpId":"localhost","allowCredentials":[{"type":"public-key","id":"TVE"}],"userVerification":"preferred"}');
        });
        $this->mock(AuthenticatorAssertionResponseValidator::class, function ($mock) {
            $mock->shouldReceive('check');
        });

        $creds = new PublicKeyCredential('public-key', $webauthnKey->credentialId, new AuthenticatorAssertionResponse(
            $this->mock(CollectedClientData::class),
            $this->mock(AuthenticatorData::class),
            'signature',
            'userHandle'
        ));
        $data = json_decode(json_encode($creds), true);
        $data['id'] = Base64UrlSafe::encodeUnpadded($webauthnKey->credentialId);
        $data['rawId'] = base64_encode($webauthnKey->credentialId);

        $test = app(CredentialAssertionValidator::class);
        $result = $test($user, $data);

        $this->assertTrue($result);
    }
}
