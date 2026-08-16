<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRestaurantTableRequest extends FormRequest
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
            'label' => ['sometimes', 'required', 'string', 'max:60'],
            'seatingCapacity' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:100'],
            'status' => ['sometimes', 'required', Rule::in(['available', 'occupied', 'needs_cleaning'])],
        ];
    }
}
