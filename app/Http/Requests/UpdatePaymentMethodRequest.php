<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'brand' => ['sometimes', 'required', 'string', 'max:50'],
            'last4' => ['sometimes', 'required', 'digits:4'],
            'expMonth' => ['sometimes', 'required', 'integer', 'between:1,12'],
            'expYear' => ['sometimes', 'required', 'integer', 'between:2000,2100'],
            'isDefault' => ['sometimes', 'boolean'],
        ];
    }
}
