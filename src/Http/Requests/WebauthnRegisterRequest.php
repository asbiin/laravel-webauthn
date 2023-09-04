<?php

namespace LaravelWebauthn\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WebauthnRegisterRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'id' => 'required|string',
            'type' => 'required|string',
            'rawId' => 'required|string',
            'response.attestationObject' => 'required|string',
            'response.clientDataJSON' => 'required|string',
            'name' => 'required|string',
        ];
    }
}
