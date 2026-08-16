<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMenuItemRequest extends FormRequest
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
                'required',
                Rule::exists('categories', 'id')->where('workspace_id', $workspaceId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'basePrice' => ['required', 'numeric', 'min:0'],
            'isAvailable' => ['nullable', 'boolean'],
            'dietaryTags' => ['nullable', 'array'],
            'dietaryTags.*' => ['string', 'max:40'],
            'variants' => ['nullable', 'array'],
            'variants.*.name' => ['required_with:variants', 'string', 'max:120'],
            'variants.*.priceModifier' => ['required_with:variants', 'numeric'],
            'addOns' => ['nullable', 'array'],
            'addOns.*.name' => ['required_with:addOns', 'string', 'max:120'],
            'addOns.*.priceModifier' => ['required_with:addOns', 'numeric'],
            'imageUrl' => ['nullable', 'url', 'max:2048'],
        ];
    }
}
