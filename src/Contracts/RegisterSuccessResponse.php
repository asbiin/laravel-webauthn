<?php

namespace LaravelWebauthn\Contracts;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * @psalm-mutable
 */
interface RegisterSuccessResponse extends Responsable
{
    /**
     * Set the new webauthn key.
     *
     * @psalm-pure
     */
    public function setWebauthnKey(Request $request, Model $webauthnKey): self;
}
