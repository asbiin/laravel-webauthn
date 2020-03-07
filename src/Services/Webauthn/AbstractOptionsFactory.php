<?php

namespace LaravelWebauthn\Services\Webauthn;

use Webauthn\AuthenticationExtensions\AuthenticationExtension;
use Webauthn\AuthenticationExtensions\AuthenticationExtensionsClientInputs;

abstract class AbstractOptionsFactory extends AbstractFactory
{
    protected function createExtensions(): AuthenticationExtensionsClientInputs
    {
        $extensions = new AuthenticationExtensionsClientInputs();

        $array = $this->config->get('webauthn.extensions', []);
        foreach ($array as $k => $v) {
            $extensions->add(new AuthenticationExtension($k, $v));
        }

        return $extensions;
    }
}
