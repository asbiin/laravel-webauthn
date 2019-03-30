<?php

namespace LaravelWebauthn\Services\Webauthn;

use Illuminate\Support\Facades\Auth;
use Webauthn\AttestedCredentialData;
use LaravelWebauthn\Models\WebauthnKey;
use Webauthn\CredentialRepository as WebauthnCredentialRepository;

class CredentialRepository implements WebauthnCredentialRepository
{
    public function has(string $credentialId): bool
    {
        $userId = Auth::id();
        if ($userId) {
            return WebauthnKey::where([
                'user_id' => $userId,
                'credentialId' => base64_encode($credentialId),
            ])->count() !== 0;
        }

        return false;
    }

    public function get(string $credentialId): AttestedCredentialData
    {
        $webAuthn = $this->model($credentialId);

        return $webAuthn->attestedCredentialData;
    }

    public function getUserHandleFor(string $credentialId): string
    {
        $webAuthn = $this->model($credentialId);

        return $webAuthn->user_id;
    }

    public function getCounterFor(string $credentialId): int
    {
        $webAuthn = $this->model($credentialId);

        return $webAuthn->counter;
    }

    public function updateCounterFor(string $credentialId, int $newCounter): void
    {
        $webAuthn = $this->model($credentialId);
        $webAuthn->counter = $newCounter;
        $webAuthn->save();
    }

    private function model(string $credentialId)
    {
        $userId = Auth::id();
        if ($userId) {
            return WebauthnKey::where([
                'user_id' => $userId,
                'credentialId' => base64_encode($credentialId),
            ])->firstOrFail();
        }
    }
}
