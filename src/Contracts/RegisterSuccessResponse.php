<?php

namespace LaravelWebauthn\Contracts;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;

interface RegisterSuccessResponse extends Responsable
{
    /**
     * Get the id of the registerd key.
     *
     * @param  Request  $request
     * @param  int  $webauthnId
     * @return self
     */
    public function setWebauthnId(Request $request, int $webauthnId): self;
}
