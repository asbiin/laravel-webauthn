<?php

namespace LaravelWebauthn\Contracts;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

interface RegisterSuccessResponse extends Responsable
{
    /**
     * Set the new webauthn key.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Database\Eloquent\Model  $webauthnKey
     * @return self
     */
    public function setWebauthnKey(Request $request, Model $webauthnKey): self;
}
