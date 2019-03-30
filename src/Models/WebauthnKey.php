<?php

namespace LaravelWebauthn\Models;

use Webauthn\AttestedCredentialData;
use Illuminate\Database\Eloquent\Model;
use Webauthn\PublicKeyCredentialDescriptor;

class WebauthnKey extends Model
{
    protected $table = 'webauthn';

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
        'publicKeyCredentialDescriptor',
        'attestedCredentialData',
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
    ];

    /**
     * Mutator credentialId.
     *
     * @param string|null $value
     */
    public function setCredentialIdAttribute($value)
    {
        $this->attributes['credentialId'] = ! is_null($value) ? base64_encode($value) : $value;
    }

    /**
     * Get the publicKeyCredentialDescriptor.
     *
     * @return PublicKeyCredentialDescriptor
     */
    public function getPublicKeyCredentialDescriptorAttribute($value)
    {
        $json = \Safe\json_decode($value, true);

        return PublicKeyCredentialDescriptor::createFromJson($json);
    }

    /**
     * Mutator publicKeyCredentialDescriptor.
     *
     * @param string|null $value
     */
    public function setPublicKeyCredentialDescriptorAttribute($value)
    {
        $this->attributes['publicKeyCredentialDescriptor'] = json_encode($value);
    }

    /**
     * Get the attestedCredentialData.
     *
     * @return AttestedCredentialData
     */
    public function getAttestedCredentialDataAttribute($value)
    {
        $json = \Safe\json_decode($value, true);

        return AttestedCredentialData::createFromJson($json);
    }

    /**
     * Mutator attestedCredentialData.
     *
     * @param string|null $value
     */
    public function setAttestedCredentialDataAttribute($value)
    {
        $this->attributes['attestedCredentialData'] = json_encode($value);
    }
}
