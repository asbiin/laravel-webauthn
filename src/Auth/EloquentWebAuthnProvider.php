<?php

namespace LaravelWebauthn\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use LaravelWebauthn\Events\WebauthnLogin;
use LaravelWebauthn\Facades\Webauthn;
use LaravelWebauthn\Services\Webauthn\CredentialAssertionValidator;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Webauthn\Util\Base64;

class EloquentWebAuthnProvider extends EloquentUserProvider
{
    /**
     * If it should fallback to password credentials whenever possible.
     */
    protected bool $fallback;

    /**
     * WebAuthn assertion validator.
     */
    protected CredentialAssertionValidator $validator;

    /**
     * Create a new database user provider.
     */
    public function __construct(Config $config, CredentialAssertionValidator $validator, Hasher $hasher, string $model)
    {
        $this->fallback = (bool) $config->get('webauthn.fallback', true);
        $this->validator = $validator;

        parent::__construct($hasher, $model);
    }

    /**
     * Retrieve a user by the given credentials.
     */
    public function retrieveByCredentials(array $credentials): ?User
    {
        if ($this->isSignedChallenge($credentials)) {
            try {
                $webauthnKey = (Webauthn::model())::where('credentialId', Base64UrlSafe::encode(Base64::decode($credentials['id'])))
                    ->orWhere('credentialId', Base64UrlSafe::encodeUnpadded(Base64::decode($credentials['id'])))
                    ->firstOrFail();

                return $this->retrieveById($webauthnKey->user_id);
            } catch (ModelNotFoundException $e) {
                // No result
            }
        }

        return parent::retrieveByCredentials($credentials);
    }

    /**
     * Check if the credentials are for a public key signed challenge.
     */
    protected function isSignedChallenge(array $credentials): bool
    {
        return isset($credentials['id'], $credentials['rawId'], $credentials['type'], $credentials['response']);
    }

    /**
     * Validate a user against the given credentials.
     */
    public function validateCredentials(User $user, array $credentials): bool
    {
        if ($this->isSignedChallenge($credentials)
            && Webauthn::validateAssertion($user, $credentials)) {
            WebauthnLogin::dispatch($user, true);

            return true;
        }

        // If the fallback is enabled, we will validate the credential password.
        if ($this->fallback) {
            return parent::validateCredentials($user, $credentials);
        }

        return false;
    }
}
