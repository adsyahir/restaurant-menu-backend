<?php

namespace App\Http\Requests;

use App\Models\City;
use App\Models\Postcode;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Public registration endpoint — anyone may sign up.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Malaysia uses the cascading dropdowns (FK ids); other countries type
        // their location as free text — mirrors the register UI.
        $isMalaysia = strtoupper((string) $this->input('country_code')) === 'MY';

        return [
            // --- Owner account ---
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email:rfc', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::defaults()],

            // --- Workspace (tenant) ---
            'restaurant_name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'lowercase',
                'min:3',
                'max:63',
                'regex:/^[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/', // valid subdomain label
                Rule::notIn($this->reservedSlugs()),
                Rule::unique('workspaces', 'slug'),
            ],
            'cuisine' => ['nullable', 'string', 'max:50'],

            // --- Address ---
            'address' => ['nullable', 'string', 'max:255'],
            'country_code' => ['required', 'string', 'size:2', Rule::in($this->allowedCountryCodes())],

            // Malaysia: structured selection from the lookup tables.
            'state_id' => [Rule::prohibitedIf(! $isMalaysia), 'nullable', 'integer', Rule::exists('states', 'id')],
            'city_id' => [Rule::prohibitedIf(! $isMalaysia), 'nullable', 'integer', Rule::exists('cities', 'id')],
            'postcode_id' => [Rule::prohibitedIf(! $isMalaysia), 'nullable', 'integer', Rule::exists('postcodes', 'id')],

            // Free text — used for other countries (also stores the mirrored
            // names for Malaysia, so kept nullable in both cases).
            'state' => ['nullable', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:120'],
            'postcode' => ['nullable', 'string', 'max:16'],

            // --- Subscription ---
            'plan' => ['required', Rule::in(['free', 'pro', 'business'])],

            // --- Legal ---
            'terms' => ['accepted'],
        ];
    }

    /**
     * Additional validation after the primary rules pass.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (strtoupper((string) $this->input('country_code')) !== 'MY') {
                    return;
                }

                $stateId = $this->input('state_id');
                $cityId = $this->input('city_id');
                $postcodeId = $this->input('postcode_id');

                // The chosen city must belong to the chosen state.
                if ($cityId && $stateId && ! City::where('id', $cityId)->where('state_id', $stateId)->exists()) {
                    $validator->errors()->add('city_id', 'The selected city does not belong to the chosen state.');
                }

                // The chosen postcode must belong to the chosen city.
                if ($postcodeId && $cityId && ! Postcode::where('id', $postcodeId)->where('city_id', $cityId)->exists()) {
                    $validator->errors()->add('postcode_id', 'The selected postcode does not belong to the chosen city.');
                }
            },
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => is_string($this->email) ? strtolower(trim($this->email)) : $this->email,
            'country_code' => is_string($this->country_code) ? strtoupper(trim($this->country_code)) : $this->country_code,
            'slug' => is_string($this->slug) ? strtolower(trim($this->slug)) : $this->slug,
        ]);
    }

    /**
     * Human-friendly attribute names for error messages.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'full name',
            'restaurant_name' => 'restaurant name',
            'slug' => 'subdomain',
            'country_code' => 'country',
        ];
    }

    /**
     * Custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'terms.accepted' => 'You must agree to the Terms and Privacy Policy.',
            'slug.regex' => 'The subdomain may only contain lowercase letters, numbers and hyphens.',
            'slug.not_in' => 'That subdomain is reserved. Please choose another.',
            'slug.unique' => 'That subdomain is already taken. Please choose another.',
        ];
    }

    /**
     * ISO 3166-1 alpha-2 codes the app currently supports.
     *
     * @return array<int, string>
     */
    protected function allowedCountryCodes(): array
    {
        return ['MY', 'SG', 'ID', 'TH', 'PH', 'VN', 'AU', 'GB', 'US', 'AE', 'IN', 'JP'];
    }

    /**
     * Subdomains reserved for the platform (must mirror the frontend list).
     *
     * @return array<int, string>
     */
    protected function reservedSlugs(): array
    {
        return ['www', 'app', 'api', 'admin', 'mail', 'blog', 'status', 'help', 'support', 'dashboard'];
    }
}
