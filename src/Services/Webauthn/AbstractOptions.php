<?php

namespace LaravelWebauthn\Services\Webauthn;

use Illuminate\Contracts\Config\Repository as Config;
use Webauthn\AuthenticationExtensions\AuthenticationExtension;
use Webauthn\AuthenticationExtensions\AuthenticationExtensionsClientInputs;

abstract class AbstractOptions
{
    /**
     * The config repository instance.
     *
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    protected function createExtensions(): AuthenticationExtensionsClientInputs
    {
        $extensions = new AuthenticationExtensionsClientInputs();

        $array = $this->config->get('webauthn.extensions') ?: [];
        foreach ($array as $k => $v) {
            $extensions->add(new AuthenticationExtension($k, $v));
        }

        return $extensions;
    }
}
