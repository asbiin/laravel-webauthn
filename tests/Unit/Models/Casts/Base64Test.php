<?php

namespace LaravelWebauthn\Tests\Unit\Models;

use LaravelWebauthn\Models\Casts\Base64;
use LaravelWebauthn\Models\WebauthnKey;
use LaravelWebauthn\Tests\FeatureTestCase;

class Base64Test extends FeatureTestCase
{
    /**
     * @test
     *
     * @dataProvider dataProvider
     */
    public function it_deserialize_credentialId($credentialId, $expected)
    {
        $webauthnKey = new WebauthnKey();

        $bin = (new Base64)->get($webauthnKey, 'credentialId', $credentialId, []);

        $this->assertEquals($expected, (new Base64)->set($webauthnKey, 'credentialId', $bin, []));
    }

    public static function dataProvider()
    {
        return [
            [
                'xrXvxSyol4aHHmmYLBcJyln6pAHgjc/+6UnE2EX4ZGl5Vw82/AjX/5wryErEUfeIBU4djcj2HMXWv0e+Ck/GbA==',
                'xrXvxSyol4aHHmmYLBcJyln6pAHgjc_-6UnE2EX4ZGl5Vw82_AjX_5wryErEUfeIBU4djcj2HMXWv0e-Ck_GbA==',
            ],
        ];
    }
}
