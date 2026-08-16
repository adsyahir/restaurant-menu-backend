<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkspaceRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'emoji' => ['sometimes', 'nullable', 'string', 'max:8'],
            'cuisine' => ['sometimes', 'nullable', 'string', 'max:50'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'state' => ['sometimes', 'nullable', 'string', 'max:120'],
            'city' => ['sometimes', 'nullable', 'string', 'max:120'],
            'postcode' => ['sometimes', 'nullable', 'string', 'max:16'],
            'timezone' => ['sometimes', 'required', 'string', 'max:64'],
            'currency' => ['sometimes', 'required', 'string', 'size:3'],
            'plan' => ['sometimes', 'required', Rule::in(['free', 'pro', 'business'])],
            'subscriptionStatus' => ['sometimes', 'required', Rule::in(['active', 'past_due', 'trialing'])],
            'renewsOn' => ['sometimes', 'nullable', 'date'],
        ];
    }
}
