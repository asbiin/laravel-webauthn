<?php

namespace LaravelWebauthn\Tests\Unit\Http\Controllers;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use LaravelWebauthn\Actions\ConfirmKey;
use LaravelWebauthn\Tests\FeatureTestCase;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;

class ConfirmableKeyControllerTest extends FeatureTestCase
{
    use DatabaseTransactions;

    #[Test]
    public function it_confirms_key()
    {
        $this->signIn();
        $this->mock(ConfirmKey::class, function (MockInterface $mock) {
            $mock->shouldReceive('__invoke')->andReturn(true);
        });

        $response = $this->post('/webauthn/confirm-key', [], ['accept' => 'application/json']);

        $response->assertStatus(201);
    }

    #[Test]
    public function it_rejects_key()
    {
        $this->signIn();
        $this->mock(ConfirmKey::class, function (MockInterface $mock) {
            $mock->shouldReceive('__invoke')->andReturn(false);
        });

        $response = $this->post('/webauthn/confirm-key', [], ['accept' => 'application/json']);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Invalid key.',
        ]);
    }
}
