<?php

namespace LaravelWebauthn\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WebauthnUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }
}
