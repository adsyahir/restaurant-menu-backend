<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderRequest extends FormRequest
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
            'tableId' => [
                'sometimes',
                'nullable',
                Rule::exists('restaurant_tables', 'id')->where('workspace_id', $workspaceId),
            ],
            'status' => ['sometimes', 'required', Rule::in(['placed', 'preparing', 'ready', 'served', 'paid', 'cancelled'])],
            'notes' => ['sometimes', 'nullable', 'string'],
            'paymentMethod' => ['sometimes', 'nullable', Rule::in(['cash', 'card', 'other'])],
            // Optionally replace the full line-item set.
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.menuItemId' => [
                'nullable',
                Rule::exists('menu_items', 'id')->where('workspace_id', $workspaceId),
            ],
            'items.*.name' => ['required_with:items', 'string', 'max:255'],
            'items.*.variantLabel' => ['nullable', 'string', 'max:120'],
            'items.*.unitPrice' => ['required_with:items', 'numeric', 'min:0'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }
}
