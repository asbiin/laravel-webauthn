<?php

namespace LaravelWebauthn\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use LaravelWebauthn\Exceptions\WrongUserHandleException;
use LaravelWebauthn\Models\Casts\Base64;
use LaravelWebauthn\Models\Casts\TrustPath;
use LaravelWebauthn\Models\Casts\Uuid;
use Symfony\Component\Uid\NilUuid;
use Webauthn\PublicKeyCredentialSource;

class WebauthnKey extends Model
{
    protected $table = 'webauthn_keys';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var string[]|bool
     */
    protected $guarded = ['id'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'credentialId',
        'type',
        'transports',
        'attestationType',
        'trustPath',
        'aaguid',
        'credentialPublicKey',
        'counter',
        'timestamp',
    ];

    /**
     * The attributes that should be visible in serialization.
     *
     * @var array<int,string>
     */
    protected $visible = [
        'id',
        'name',
        'type',
        'transports',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string,string>
     */
    protected $casts = [
        'counter' => 'integer',
        'transports' => 'array',
        'credentialId' => Base64::class,
        'credentialPublicKey' => Base64::class,
        'aaguid' => Uuid::class,
        'trustPath' => TrustPath::class,
    ];

    /**
     * Get PublicKeyCredentialSource object from WebauthnKey attributes.
     *
     * @return Attribute<PublicKeyCredentialSource,PublicKeyCredentialSource>
     */
    public function publicKeyCredentialSource(): Attribute
    {
        return Attribute::make(
            get: fn (): PublicKeyCredentialSource => new PublicKeyCredentialSource(
                $this->credentialId,
                $this->type,
                $this->transports,
                $this->attestationType,
                $this->trustPath,
                $this->aaguid ?? new NilUuid(),
                $this->credentialPublicKey,
                (string) $this->user_id,
                $this->counter
            ),
            set: function (PublicKeyCredentialSource $value, array $attributes = null): array {
                if (((string) Arr::get($attributes, 'user_id')) !== $value->userHandle) {
                    throw new WrongUserHandleException();
                }

                // Set value to attributes using casts
                $this->credentialId = $value->publicKeyCredentialId;
                $this->transports = $value->transports;
                $this->trustPath = $value->trustPath;
                $this->aaguid = $value->aaguid;
                $this->credentialPublicKey = $value->credentialPublicKey;
                $this->counter = $value->counter;

                return [
                    'credentialId' => $this->attributes['credentialId'],
                    'type' => $value->type,
                    'transports' => $this->attributes['transports'],
                    'attestationType' => $value->attestationType,
                    'trustPath' => $this->attributes['trustPath'],
                    'aaguid' => $this->attributes['aaguid'],
                    'credentialPublicKey' => $this->attributes['credentialPublicKey'],
                    'counter' => $this->attributes['counter'],
                ];
            }
        )->shouldCache();
    }
}
