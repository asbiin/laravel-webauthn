<?php

namespace LaravelWebauthn\Tests\Unit\Actions;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use LaravelWebauthn\Actions\UpdateKey;
use LaravelWebauthn\Models\WebauthnKey;
use LaravelWebauthn\Tests\FeatureTestCase;

class UpdateKeyTest extends FeatureTestCase
{
    use DatabaseTransactions;

    /**
     * @test
     */
    public function it_update_key()
    {
        $user = $this->user();
        $webauthnKey = factory(WebauthnKey::class)->create([
            'user_id' => $user->getAuthIdentifier(),
        ]);

        app(UpdateKey::class)($user, $webauthnKey->id, 'new-name');

        $this->assertDatabaseHas('webauthn_keys', [
            'id' => $webauthnKey->id,
            'user_id' => $user->getAuthIdentifier(),
            'name' => 'new-name',
        ]);
    }

    /**
     * @test
     */
    public function it_fails_if_wrong_user()
    {
        $user = $this->user();
        $webauthnKey = factory(WebauthnKey::class)->create();

        $this->expectException(ModelNotFoundException::class);
        app(UpdateKey::class)($user, $webauthnKey->id, 'new-name');
    }

    /**
     * @test
     */
    public function it_fails_if_wrong_id()
    {
        $user = $this->user();

        $this->expectException(ModelNotFoundException::class);
        app(UpdateKey::class)($user, 0, 'new-name');
    }
}
