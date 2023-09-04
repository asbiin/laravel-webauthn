<?php

namespace LaravelWebauthn\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WebauthnLoginRequest extends FormRequest
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
            'response.authenticatorData' => 'required|string',
            'response.clientDataJSON' => 'required|string',
            'response.signature' => 'required|string',
            'response.userHandle' => 'sometimes|nullable',
            'remember' => 'nullable|string',
        ];
    }
}
