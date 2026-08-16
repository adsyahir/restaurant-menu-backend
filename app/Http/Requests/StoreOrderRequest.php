<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
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
                'nullable',
                Rule::exists('restaurant_tables', 'id')->where('workspace_id', $workspaceId),
            ],
            'status' => ['nullable', Rule::in(['placed', 'preparing', 'ready', 'served', 'paid', 'cancelled'])],
            'notes' => ['nullable', 'string'],
            'paymentMethod' => ['nullable', Rule::in(['cash', 'card', 'other'])],
            'items' => ['required', 'array', 'min:1'],
            'items.*.menuItemId' => [
                'nullable',
                Rule::exists('menu_items', 'id')->where('workspace_id', $workspaceId),
            ],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.variantLabel' => ['nullable', 'string', 'max:120'],
            'items.*.unitPrice' => ['required', 'numeric', 'min:0'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }
}
