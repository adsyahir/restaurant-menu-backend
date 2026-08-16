<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMenuItemRequest extends FormRequest
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
        $workspaceId = $this->user()->current_workspace_id;

        return [
            'categoryId' => [
                'sometimes',
                'required',
                Rule::exists('categories', 'id')->where('workspace_id', $workspaceId),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'basePrice' => ['sometimes', 'required', 'numeric', 'min:0'],
            'isAvailable' => ['sometimes', 'boolean'],
            'dietaryTags' => ['sometimes', 'nullable', 'array'],
            'dietaryTags.*' => ['string', 'max:40'],
            'variants' => ['sometimes', 'nullable', 'array'],
            'variants.*.name' => ['required_with:variants', 'string', 'max:120'],
            'variants.*.priceModifier' => ['required_with:variants', 'numeric'],
            'addOns' => ['sometimes', 'nullable', 'array'],
            'addOns.*.name' => ['required_with:addOns', 'string', 'max:120'],
            'addOns.*.priceModifier' => ['required_with:addOns', 'numeric'],
            'imageUrl' => ['sometimes', 'nullable', 'url', 'max:2048'],
        ];
    }
}
