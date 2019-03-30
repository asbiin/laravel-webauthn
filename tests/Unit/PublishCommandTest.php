<?php

namespace LaravelWebauthn\Tests\Unit;

use LaravelWebauthn\Tests\FeatureTestCase;

class PublishCommandTest extends FeatureTestCase
{
    public function test_command()
    {
        $this->artisan('laravelwebauthn:publish')
            ->expectsOutput('Publishing complete.')
            ->expectsOutput('Publishing complete.');
    }
}
