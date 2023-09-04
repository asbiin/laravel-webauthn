<?php

namespace LaravelWebauthn\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use LaravelWebauthn\Actions\DeleteKey;
use LaravelWebauthn\Actions\PrepareCreationData;
use LaravelWebauthn\Actions\UpdateKey;
use LaravelWebauthn\Actions\ValidateKeyCreation;
use LaravelWebauthn\Contracts\DestroyResponse;
use LaravelWebauthn\Contracts\RegisterSuccessResponse;
use LaravelWebauthn\Contracts\RegisterViewResponse;
use LaravelWebauthn\Contracts\UpdateResponse;
use LaravelWebauthn\Http\Requests\WebauthnRegisterRequest;
use LaravelWebauthn\Http\Requests\WebauthnUpdateRequest;

class WebauthnKeyController extends Controller
{
    /**
     * Return the register data to attempt a Webauthn registration.
     */
    public function create(Request $request): RegisterViewResponse
    {
        $publicKey = app(PrepareCreationData::class)($request->user());

        return app(RegisterViewResponse::class)
            ->setPublicKey($request, $publicKey);
    }

    /**
     * Validate and create the Webauthn request.
     */
    public function store(WebauthnRegisterRequest $request): RegisterSuccessResponse
    {
        $webauthnKey = app(ValidateKeyCreation::class)(
            $request->user(),
            $request->only(['id', 'rawId', 'response', 'type']),
            $request->input('name')
        );

        return app(RegisterSuccessResponse::class)
            ->setWebauthnKey($request, $webauthnKey);
    }

    /**
     * Update an existing Webauthn key.
     */
    public function update(WebauthnUpdateRequest $request, int $webauthnKeyId): UpdateResponse
    {
        app(UpdateKey::class)(
            $request->user(),
            $webauthnKeyId,
            $request->input('name')
        );

        return app(UpdateResponse::class);
    }

    /**
     * Delete an existing Webauthn key.
     */
    public function destroy(Request $request, int $webauthnKeyId): DestroyResponse
    {
        app(DeleteKey::class)(
            $request->user(),
            $webauthnKeyId
        );

        return app(DestroyResponse::class);
    }
}
