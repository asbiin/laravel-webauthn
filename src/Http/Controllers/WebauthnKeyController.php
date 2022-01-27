<?php

namespace LaravelWebauthn\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use LaravelWebauthn\Actions\DeleteKey;
use LaravelWebauthn\Actions\RegisterKeyPrepare;
use LaravelWebauthn\Actions\RegisterKeyStore;
use LaravelWebauthn\Actions\UpdateKey;
use LaravelWebauthn\Contracts\DestroyResponse;
use LaravelWebauthn\Contracts\RegisterSuccessResponse;
use LaravelWebauthn\Contracts\RegisterViewResponse;
use LaravelWebauthn\Contracts\UpdateResponse;
use LaravelWebauthn\Http\Requests\RegisterRequest;
use LaravelWebauthn\Http\Requests\UpdateRequest;
use LaravelWebauthn\Services\Webauthn;

class WebauthnKeyController extends Controller
{
    /**
     * Return the register data to attempt a Webauthn registration.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return RegisterViewResponse
     */
    public function create(Request $request)
    {
        $publicKey = app(RegisterKeyPrepare::class)($request->user());

        $request->session()->put(Webauthn::SESSION_PUBLICKEY_CREATION, $publicKey);

        return app(RegisterViewResponse::class);
    }

    /**
     * Validate and create the Webauthn request.
     *
     * @param  RegisterRequest  $request
     * @return RegisterSuccessResponse
     */
    public function store(RegisterRequest $request)
    {
        $publicKey = $request->session()->pull(Webauthn::SESSION_PUBLICKEY_CREATION);

        if (! $publicKey instanceof \Webauthn\PublicKeyCredentialCreationOptions) {
            abort(404);
        }

        /** @var \LaravelWebauthn\Models\WebauthnKey|null */
        $webauthnKey = app(RegisterKeyStore::class)(
            $request->user(),
            $publicKey,
            $request->input('register'),
            $request->input('name')
        );

        if ($webauthnKey !== null) {
            $request->session()->put(Webauthn::SESSION_WEBAUTHNID_CREATED, $webauthnKey->id);
        }

        return app(RegisterSuccessResponse::class);
    }

    /**
     * Update an existing Webauthn key.
     *
     * @param  UpdateRequest  $request
     * @param  int  $webauthnKeyId
     * @return UpdateResponse
     */
    public function update(UpdateRequest $request, int $webauthnKeyId)
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
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $webauthnKeyId
     * @return DestroyResponse
     */
    public function destroy(Request $request, int $webauthnKeyId)
    {
        app(DeleteKey::class)(
            $request->user(),
            $webauthnKeyId
        );

        return app(DestroyResponse::class);
    }
}
