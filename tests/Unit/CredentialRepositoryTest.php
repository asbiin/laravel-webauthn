<?php

namespace LaravelWebauthn\Tests\Unit;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use LaravelWebauthn\Models\WebauthnKey;
use LaravelWebauthn\Services\Webauthn\CredentialRepository;
use LaravelWebauthn\Tests\FeatureTestCase;
use Webauthn\PublicKeyCredentialUserEntity;

class CredentialRepositoryTest extends FeatureTestCase
{
    use DatabaseTransactions;

    public function test_find_one()
    {
        $user = $this->signIn();
        $webauthnKey = factory(WebauthnKey::class)->create([
            'user_id' => $user->getAuthIdentifier(),
        ]);

        $publicKey = $this->app->make(CredentialRepository::class)
            ->findOneByCredentialId($webauthnKey->credentialId);

        $this->assertNotNull($publicKey);
    }

    public function test_find_one_null()
    {
        $user = $this->signIn();

        $publicKey = $this->app->make(CredentialRepository::class)
            ->findOneByCredentialId('123');

        $this->assertNull($publicKey);
    }

    public function test_find_one_null_wrong_user()
    {
        $user = $this->signIn();
        $webauthnKey = factory(WebauthnKey::class)->create([
            'user_id' => '1',
        ]);

        $publicKey = $this->app->make(CredentialRepository::class)
            ->findOneByCredentialId($webauthnKey->credentialId);

        $this->assertNull($publicKey);
    }

    public function test_find_all()
    {
        $user = $this->signIn();
        factory(WebauthnKey::class)->create([
            'user_id' => $this->user()->getAuthIdentifier(),
        ]);
        factory(WebauthnKey::class)->create([
            'user_id' => $user->getAuthIdentifier(),
        ]);

        $publicKeys = $this->app->make(CredentialRepository::class)
            ->findAllForUserEntity(new PublicKeyCredentialUserEntity('name', $user->getAuthIdentifier(), 'name'));

        $this->assertNotNull($publicKeys);
        $this->assertCount(1, $publicKeys);
    }

    public function test_save_credential()
    {
        $user = $this->signIn();
        $webauthnKey = factory(WebauthnKey::class)->create([
            'user_id' => $user->getAuthIdentifier(),
        ]);

        $publicKeyCredentialSource = $webauthnKey->publicKeyCredentialSource;
        $publicKeyCredentialSource->setCounter(154);

        $this->app->make(CredentialRepository::class)
            ->saveCredentialSource($publicKeyCredentialSource);

        $this->assertDatabaseHas('webauthn_keys', [
            'user_id' => $user->getAuthIdentifier(),
            'counter' => '154',
        ]);
    }
}
