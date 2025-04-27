<?php

namespace LaravelWebauthn\Tests\Unit\Actions;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use LaravelWebauthn\Actions\DeleteKey;
use LaravelWebauthn\Models\WebauthnKey;
use LaravelWebauthn\Tests\FeatureTestCase;
use PHPUnit\Framework\Attributes\Test;

class DeleteKeyTest extends FeatureTestCase
{
    use DatabaseTransactions;

    #[Test]
    public function it_delete_key()
    {
        $user = $this->user();
        $webauthnKey = factory(WebauthnKey::class)->create([
            'user_id' => $user->getAuthIdentifier(),
        ]);

        app(DeleteKey::class)($user, $webauthnKey->id);

        $this->assertDatabaseMissing('webauthn_keys', [
            'id' => $webauthnKey->id,
        ]);
    }

    #[Test]
    public function it_fails_if_wrong_user()
    {
        $user = $this->user();
        $webauthnKey = factory(WebauthnKey::class)->create();

        $this->expectException(ModelNotFoundException::class);
        app(DeleteKey::class)($user, $webauthnKey->id);
    }

    #[Test]
    public function it_fails_if_wrong_id()
    {
        $user = $this->user();

        $this->expectException(ModelNotFoundException::class);
        app(DeleteKey::class)($user, 0);
    }
}
