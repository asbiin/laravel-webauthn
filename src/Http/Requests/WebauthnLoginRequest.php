<?php

namespace LaravelWebauthn\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WebauthnLoginRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'data' => 'required|string',
        ];
    }
}
