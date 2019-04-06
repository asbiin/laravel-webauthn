<?php

namespace LaravelWebauthn\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\URL;
use LaravelWebauthn\Facades\Webauthn;
use LaravelWebauthn\Models\WebauthnKey;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class WebauthnController extends Controller
{
    /**
     * PublicKey Creation session name.
     *
     * @var string
     */
    private const SESSION_PUBLICKEY_CREATION = 'webauthn.publicKeyCreation';

    /**
     * PublicKey Request session name.
     *
     * @var string
     */
    private const SESSION_PUBLICKEY_REQUEST = 'webauthn.publicKeyRequest';

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

    /**
     * Show the login Webauthn request after a login authentication.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     */
    public function login(Request $request)
    {
        $publicKey = Webauthn::getAuthenticateData($request->user());

        $request->session()->put(self::SESSION_PUBLICKEY_REQUEST, $publicKey);

        return view($this->config->get('webauthn.authenticate.view'))
            ->withCallback($request->query('callback', URL::current()))
            ->withPublicKey($publicKey);
    }

    /**
     * Authenticate a webauthn request.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function auth(Request $request)
    {
        try {
            $publicKey = $request->session()->pull(self::SESSION_PUBLICKEY_REQUEST);
            if (! $publicKey instanceof \Webauthn\PublicKeyCredentialRequestOptions) {
                throw new ModelNotFoundException('Authentication data not found');
            }

            $result = Webauthn::doAuthenticate(
                $request->user(),
                $publicKey,
                $this->input($request, 'data')
            );

            return $this->redirectAfterSuccessAuth($result);
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'message' => $e->getMessage(),
                ],
            ], 403);
        }
    }

    /**
     * Return the redirect destination after a successfull auth.
     *
     * @param bool $result
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    protected function redirectAfterSuccessAuth($result)
    {
        if (! empty($this->config->get('webauthn.authenticate.postSuccessRedirectRoute'))) {
            return Redirect::intended($this->config->get('webauthn.authenticate.postSuccessRedirectRoute'));
        } else {
            return response()->json([
                'result' => $result,
            ]);
        }
    }

    /**
     * Return the register data to attempt a Webauthn registration.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $publicKey = Webauthn::getRegisterData($request->user());

        $request->session()->put(self::SESSION_PUBLICKEY_CREATION, $publicKey);

        return response()->json([
            'publicKey' => $publicKey,
        ]);
    }

    /**
     * Validate and create the Webauthn request.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {
        try {
            $publicKey = $request->session()->pull(self::SESSION_PUBLICKEY_CREATION);
            if (! $publicKey instanceof \Webauthn\PublicKeyCredentialCreationOptions) {
                throw new ModelNotFoundException('Register data not found');
            }

            $webauthnKey = Webauthn::doRegister(
                $request->user(),
                $publicKey,
                $this->input($request, 'register'),
                $this->input($request, 'name')
            );

            return response()->json([
                'result' => true,
                'id' => $webauthnKey->id,
                'object' => 'webauthnKey',
                'name' => $webauthnKey->name,
                'counter' => $webauthnKey->counter,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'message' => $e->getMessage(),
                ],
            ], 403);
        }
    }

    /**
     * Remove an existing Webauthn key.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function remove(Request $request, int $webauthnKeyId)
    {
        try {
            WebauthnKey::where('user_id', $request->user()->getAuthIdentifier())
                ->findOrFail($webauthnKeyId)
                ->delete();

            return response()->json([
                'deleted' => true,
                'id' => $webauthnKeyId,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'error' => [
                    'message' => 'Object not found',
                ],
            ], 404);
        }
    }

    /**
     * Retrieve the input with a string result.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $name
     * @param string $default
     * @return string
     */
    private function input(Request $request, string $name, string $default = ''): string
    {
        $result = $request->input($name);

        return is_string($result) ? $result : $default;
    }
}
