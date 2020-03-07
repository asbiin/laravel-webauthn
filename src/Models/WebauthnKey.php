<?php

namespace LaravelWebauthn\Models;

use Illuminate\Database\Eloquent\Model;
use LaravelWebauthn\Exceptions\WrongUserHandleException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use function Safe\base64_decode;
use function Safe\json_decode;
use function Safe\json_encode;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\TrustPath\TrustPath;

class WebauthnKey extends Model
{
    protected $table = 'webauthn_keys';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
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
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'counter' => 'integer',
        'transports' => 'array',
    ];

    /**
     * Get the credentialId.
     *
     * @param string|null $value
     * @return string|null
     */
    public function getCredentialIdAttribute($value)
    {
        return ! is_null($value) ? base64_decode($value) : $value;
    }

    /**
     * Set the credentialId.
     *
     * @param string|null $value
     * @return void
     */
    public function setCredentialIdAttribute($value)
    {
        $this->attributes['credentialId'] = ! is_null($value) ? base64_encode($value) : $value;
    }

    /**
     * Get the CredentialPublicKey.
     *
     * @param string|null $value
     * @return string|null
     */
    public function getCredentialPublicKeyAttribute($value)
    {
        return ! is_null($value) ? base64_decode($value) : $value;
    }

    /**
     * Set the CredentialPublicKey.
     *
     * @param string|null $value
     * @return void
     */
    public function setCredentialPublicKeyAttribute($value)
    {
        $this->attributes['credentialPublicKey'] = ! is_null($value) ? base64_encode($value) : $value;
    }

    /**
     * Get the TrustPath.
     *
     * @param string|null $value
     * @return TrustPath|null
     */
    public function getTrustPathAttribute($value): ?TrustPath
    {
        if (! is_null($value)) {
            $json = json_decode($value, true);

            return \Webauthn\TrustPath\TrustPathLoader::loadTrustPath($json);
        }

        return null;
    }

    /**
     * Set the TrustPath.
     *
     * @param TrustPath|null $value
     * @return void
     */
    public function setTrustPathAttribute($value)
    {
        $this->attributes['trustPath'] = json_encode($value);
    }

    /**
     * Get the Aaguid.
     *
     * @param string|null $value
     * @return UuidInterface|null
     */
    public function getAaguidAttribute($value): ?UuidInterface
    {
        if (! is_null($value) && Uuid::isValid($value)) {
            return Uuid::fromString($value);
        }

        return null;
    }

    /**
     * Set the Aaguid.
     *
     * @param UuidInterface|string|null $value
     * @return void
     */
    public function setAaguidAttribute($value)
    {
        $this->attributes['aaguid'] = $value instanceof UuidInterface ? $value->toString() : (string) $value;
    }

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
            $this->aaguid ?? Uuid::fromString(Uuid::NIL),
            $this->credentialPublicKey,
            (string) $this->user_id,
            $this->counter
        );
    }

    /**
     * Set WebauthnKey attributes from a PublicKeyCredentialSource object.
     *
     * @param PublicKeyCredentialSource $value
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
