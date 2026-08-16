<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePaymentMethodRequest extends FormRequest
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
            'brand' => ['required', 'string', 'max:50'],
            'last4' => ['required', 'digits:4'],
            'expMonth' => ['required', 'integer', 'between:1,12'],
            'expYear' => ['required', 'integer', 'between:2000,2100'],
            'isDefault' => ['nullable', 'boolean'],
        ];
    }
}
