<?php

namespace LaravelWebauthn\Models;

use Illuminate\Database\Eloquent\Model;
use LaravelWebauthn\Exceptions\WrongUserHandleException;
use LaravelWebauthn\Models\Casts\Base64;
use LaravelWebauthn\Models\Casts\TrustPath;
use LaravelWebauthn\Models\Casts\Uuid;
use Symfony\Component\Uid\Uuid as UuidConvert;
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
     * @var string[]
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
     * @var array<int, string>
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
     * @var array<string, string>
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
     * @return PublicKeyCredentialSource
     */
    public function getPublicKeyCredentialSourceAttribute(): PublicKeyCredentialSource
    {
        return new PublicKeyCredentialSource(
            $this->credentialId,
            $this->type,
            $this->transports,
            $this->attestationType,
            $this->trustPath,
            $this->aaguid ?? UuidConvert::fromString('00000000-0000-0000-0000-000000000000'),
            $this->credentialPublicKey,
            (string) $this->user_id,
            $this->counter
        );
    }

    /**
     * Set WebauthnKey attributes from a PublicKeyCredentialSource object.
     *
     * @param  PublicKeyCredentialSource  $value
     * @return void
     */
    public function setPublicKeyCredentialSourceAttribute(PublicKeyCredentialSource $value)
    {
        if ((string) $this->user_id !== $value->getUserHandle()) {
            throw new WrongUserHandleException();
        }
        $this->credentialId = $value->getPublicKeyCredentialId();
        $this->type = $value->getType();
        $this->transports = $value->getTransports();
        $this->attestationType = $value->getAttestationType();
        $this->trustPath = $value->getTrustPath();
        $this->aaguid = $value->getAaguid();
        $this->credentialPublicKey = $value->getCredentialPublicKey();
        $this->counter = $value->getCounter();
    }
}
